<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

use Tuleap\AgileDashboard\BreadCrumbDropdown\AdministrationCrumbBuilder;
use Tuleap\AgileDashboard\BreadCrumbDropdown\AgileDashboardCrumbBuilder;
use Tuleap\AgileDashboard\Planning\Admin\PlanningEditURLEvent;
use Tuleap\AgileDashboard\Planning\Admin\PlanningUpdatedEvent;
use Tuleap\AgileDashboard\Planning\RootPlanning\DisplayTopPlanningAppEvent;
use Tuleap\AgileDashboard\Planning\RootPlanning\RootPlanningEditionEvent;
use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Queue\WorkerEvent;
use Tuleap\Request\CollectRoutesEvent;
use Tuleap\ScaledAgile\Program\Administration\PlannableItems\PlannableItemsCollectionBuilder;
use Tuleap\ScaledAgile\Program\Administration\PlannableItems\PlannableItemsTrackersDao;
use Tuleap\ScaledAgile\Program\Administration\PlannableItems\PlannableItemsTrackersUpdater;
use Tuleap\ScaledAgile\Program\Administration\PlannableItems\Presenter\PlannableItemsPerTeamPresenterCollectionBuilder;
use Tuleap\ScaledAgile\Program\Administration\ReadOnlyProgramAdminURLBuilder;
use Tuleap\ScaledAgile\Program\Administration\ReadOnlyProgramAdminViewController;
use Tuleap\ScaledAgile\Program\Backlog\AsynchronousCreation\ArtifactCreatedHandler;
use Tuleap\ScaledAgile\Program\Backlog\AsynchronousCreation\CreateProjectIncrementsRunner;
use Tuleap\ScaledAgile\Program\Backlog\AsynchronousCreation\PendingArtifactCreationDao;
use Tuleap\ScaledAgile\Program\Backlog\CreationCheck\ArtifactCreatorChecker;
use Tuleap\ScaledAgile\Program\Backlog\CreationCheck\ProjectIncrementArtifactCreatorChecker;
use Tuleap\ScaledAgile\Program\Backlog\CreationCheck\RequiredFieldChecker;
use Tuleap\ScaledAgile\Program\Backlog\CreationCheck\SemanticChecker;
use Tuleap\ScaledAgile\Program\Backlog\CreationCheck\StatusSemanticChecker;
use Tuleap\ScaledAgile\Program\Backlog\CreationCheck\WorkflowChecker;
use Tuleap\ScaledAgile\Program\Backlog\ProgramDao;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\ProjectIncrementArtifactLinkType;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Project\TeamProjectsCollectionBuilder;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Source\Fields\FieldArtifactLinkAdapter;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Source\Fields\FieldDescriptionAdapter;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Source\Fields\FieldStatusAdapter;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Source\Fields\FieldsTimeFrameAdapter;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Source\Fields\FieldTitleAdapter;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Source\Fields\SynchronizedFieldDataFromProgramAndTeamTrackersCollectionBuilder;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Source\Fields\SynchronizedFieldsAdapter;
use Tuleap\ScaledAgile\Program\Backlog\TrackerCollectionFactory;
use Tuleap\ScaledAgile\Program\PlanningConfiguration\PlanningAdapter;
use Tuleap\ScaledAgile\Team\RootPlanning\RootPlanningEditionHandler;
use Tuleap\ScaledAgile\Team\TeamDao;
use Tuleap\Tracker\Artifact\CanSubmitNewArtifact;
use Tuleap\Tracker\Artifact\Event\ArtifactCreated;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NaturePresenterFactory;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeBuilder;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../../agiledashboard/include/agiledashboardPlugin.php';

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
final class scaled_agilePlugin extends Plugin
{
    public function __construct(?int $id)
    {
        parent::__construct($id);
        $this->setScope(self::SCOPE_SYSTEM);
        bindtextdomain('tuleap-scaled_agile', __DIR__ . '/../site-content');
    }

    public function getHooksAndCallbacks(): Collection
    {
        $this->addHook(CollectRoutesEvent::NAME);
        $this->addHook(PlanningEditURLEvent::NAME);
        $this->addHook(RootPlanningEditionEvent::NAME);
        $this->addHook(PlanningUpdatedEvent::NAME);
        $this->addHook(DisplayTopPlanningAppEvent::NAME);
        $this->addHook(NaturePresenterFactory::EVENT_GET_ARTIFACTLINK_NATURES, 'getArtifactLinkNatures');
        $this->addHook(NaturePresenterFactory::EVENT_GET_NATURE_PRESENTER, 'getNaturePresenter');
        $this->addHook(Tracker_Artifact_XMLImport_XMLImportFieldStrategyArtifactLink::TRACKER_ADD_SYSTEM_NATURES, 'trackerAddSystemNatures');
        $this->addHook(CanSubmitNewArtifact::NAME);
        $this->addHook(ArtifactCreated::NAME);
        $this->addHook(WorkerEvent::NAME);

        return parent::getHooksAndCallbacks();
    }

    public function getDependencies(): array
    {
        return ['tracker', 'agiledashboard'];
    }

    public function getPluginInfo(): PluginInfo
    {
        if ($this->pluginInfo === null) {
            $pluginInfo = new PluginInfo($this);
            $pluginInfo->setPluginDescriptor(
                new PluginDescriptor(
                    dgettext('tuleap-scaled_agile', 'Scaled Agile Backlog'),
                    '',
                    dgettext('tuleap-scaled_agile', 'Extension of the Agile Dashboard plugin to allow planning of Backlog items across projects')
                )
            );
            $this->pluginInfo = $pluginInfo;
        }
        return $this->pluginInfo;
    }

    public function collectRoutesEvent(CollectRoutesEvent $event): void
    {
        $event->getRouteCollector()->addRoute(
            ['GET'],
            '/project/{project_name:[A-z0-9-]+}/backlog/admin/{id:\d+}',
            $this->getRouteHandler('routeGETProgramReadOnlyAdminTopPlanning')
        );
    }

    public function routeGETProgramReadOnlyAdminTopPlanning(): ReadOnlyProgramAdminViewController
    {
        $agiledashboard_plugin = PluginManager::instance()->getPluginByName(AgileDashboardPlugin::PLUGIN_NAME);
        assert($agiledashboard_plugin instanceof AgileDashboardPlugin);

        $planning_adapter = $this->getPlanningAdapter();

        return new ReadOnlyProgramAdminViewController(
            ProjectManager::instance(),
            $planning_adapter,
            new AgileDashboardCrumbBuilder(
                $agiledashboard_plugin->getPluginPath()
            ),
            new AdministrationCrumbBuilder(),
            $this->buildTemplateRenderer(),
            new PlannableItemsCollectionBuilder(
                new PlannableItemsTrackersDao(),
                TrackerFactory::instance(),
                ProjectManager::instance()
            ),
            new PlannableItemsPerTeamPresenterCollectionBuilder(
                $planning_adapter
            ),
            new IncludeAssets(
                __DIR__ . '/../../../src/www/assets/scaled_agile',
                '/assets/scaled_agile'
            ),
            $agiledashboard_plugin->getIncludeAssets()
        );
    }

    public function planningEditURLEvent(PlanningEditURLEvent $event): void
    {
        $adapter       = $this->getPlanningAdapter();
        $planning      = $adapter->buildFromPlanning($event->getPlanning());
        $root_planning = null;

        if ($event->getRootPlanning()) {
            $root_planning = $adapter->buildFromPlanning($event->getRootPlanning());
        }

        $url_builder = new ReadOnlyProgramAdminURLBuilder(
            new ProgramDao(),
            ProjectManager::instance()
        );

        $url = $url_builder->buildURL(
            $planning,
            $root_planning
        );

        if ($url !== null) {
            $event->setEditUrl($url);
        }
    }

    public function rootPlanningEditionEvent(RootPlanningEditionEvent $event): void
    {
        $handler = new RootPlanningEditionHandler(new TeamDao());
        $handler->handle($event);
    }

    public function planningUpdatedEvent(PlanningUpdatedEvent $event): void
    {
        $planning_adapter = $this->getPlanningAdapter();

        $scaled_planning = $planning_adapter->buildFromPlanning($event->getPlanning());
        (new PlannableItemsTrackersUpdater(
            new TeamDao(),
            new PlannableItemsTrackersDao(),
            new DBTransactionExecutorWithConnection(
                DBFactory::getMainTuleapDBConnection()
            ),
            $planning_adapter
        ))->updatePlannableItemsTrackersFromPlanning(
            $scaled_planning,
            $event->getUser()
        );
    }

    private function buildTemplateRenderer(): TemplateRenderer
    {
        return TemplateRendererFactory::build()->getRenderer(__DIR__ . '/../templates');
    }

    public function displayTopPlanningAppEvent(DisplayTopPlanningAppEvent $event): void
    {
        $team_projects_collection_builder = new TeamProjectsCollectionBuilder(
            new ProgramDao(),
            ProjectManager::instance()
        );

        $virtual_top_milestone   = $event->getTopMilestone();
        $team_project_collection = $team_projects_collection_builder->getTeamProjectForAGivenProgramProject(
            $virtual_top_milestone->getProject()
        );

        if ($team_project_collection->isEmpty() === true || $virtual_top_milestone->getPlanning()->getId() === null) {
            return;
        }

        $event->setBacklogItemsCannotBeAdded();

        $project_increment_creator_checker = $this->getProjectIncrementCreatorChecker();

        if (! $event->canUserCreateMilestone()) {
            return;
        }

        $planning_adapter = $this->getPlanningAdapter();
        $scaled_planning  = $planning_adapter->buildFromPlanning($virtual_top_milestone->getPlanning());

        $user_can_create_project_increment = $project_increment_creator_checker->canProjectIncrementBeCreated(
            $scaled_planning,
            $event->getUser()
        );

        if (! $user_can_create_project_increment) {
            $event->setUserCannotCreateMilestone();
        }
    }

    /**
     * @see NaturePresenterFactory::EVENT_GET_ARTIFACTLINK_NATURES
     */
    public function getArtifactLinkNatures(array $params): void
    {
        $params['natures'][] = new ProjectIncrementArtifactLinkType();
    }

    /**
     * @see NaturePresenterFactory::EVENT_GET_NATURE_PRESENTER
     */
    public function getNaturePresenter(array $params): void
    {
        if ($params['shortname'] === ProjectIncrementArtifactLinkType::ART_LINK_SHORT_NAME) {
            $params['presenter'] = new ProjectIncrementArtifactLinkType();
        }
    }

    /**
     * @see Tracker_Artifact_XMLImport_XMLImportFieldStrategyArtifactLink::TRACKER_ADD_SYSTEM_NATURES
     */
    public function trackerAddSystemNatures(array $params): void
    {
        $params['natures'][] = ProjectIncrementArtifactLinkType::ART_LINK_SHORT_NAME;
    }

    public function canSubmitNewArtifact(CanSubmitNewArtifact $can_submit_new_artifact): void
    {
        $artifact_creator_checker = new ArtifactCreatorChecker(
            $this->getPlanningAdapter(),
            $this->getProjectIncrementCreatorChecker()
        );

        if (
            ! $artifact_creator_checker->canCreateAnArtifact(
                $can_submit_new_artifact->getUser(),
                $can_submit_new_artifact->getTracker()
            )
        ) {
            $can_submit_new_artifact->disableArtifactSubmission();
        }
    }

    public function workerEvent(WorkerEvent $event): void
    {
        $create_mirrors_runner = CreateProjectIncrementsRunner::build();
        $create_mirrors_runner->addListener($event);
    }

    public function trackerArtifactCreated(ArtifactCreated $event): void
    {
        $program_dao = new ProgramDao();
        $logger      = $this->getLogger();

        $logger->debug(
            sprintf(
                "Store program create with #%d by user #%d",
                (int) $event->getArtifact()->getId(),
                (int) $event->getUser()->getId()
            )
        );

        $handler = new ArtifactCreatedHandler(
            $program_dao,
            CreateProjectIncrementsRunner::build(),
            new PendingArtifactCreationDao(),
            $this->getPlanningAdapter()
        );
        $handler->handle($event);
    }

    private function getProjectIncrementCreatorChecker(): ProjectIncrementArtifactCreatorChecker
    {
        $form_element_factory    = \Tracker_FormElementFactory::instance();
        $timeframe_dao           = new \Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeDao();
        $semantic_status_factory = new Tracker_Semantic_StatusFactory();
        $logger                  = $this->getLogger();

        return new ProjectIncrementArtifactCreatorChecker(
            new TeamProjectsCollectionBuilder(
                new ProgramDao(),
                ProjectManager::instance()
            ),
            new TrackerCollectionFactory(
                $this->getPlanningAdapter()
            ),
            new SynchronizedFieldDataFromProgramAndTeamTrackersCollectionBuilder(
                new SynchronizedFieldsAdapter(
                    new FieldArtifactLinkAdapter($form_element_factory),
                    new FieldTitleAdapter(new Tracker_Semantic_TitleFactory()),
                    new FieldDescriptionAdapter(new Tracker_Semantic_DescriptionFactory()),
                    new FieldStatusAdapter($semantic_status_factory),
                    new FieldsTimeFrameAdapter(new SemanticTimeframeBuilder($timeframe_dao, $form_element_factory))
                )
            ),
            new SemanticChecker(
                new \Tracker_Semantic_TitleDao(),
                new \Tracker_Semantic_DescriptionDao(),
                $timeframe_dao,
                new StatusSemanticChecker(new Tracker_Semantic_StatusDao(), $semantic_status_factory),
            ),
            new RequiredFieldChecker($logger),
            new WorkflowChecker(
                new Workflow_Dao(),
                new Tracker_Rule_Date_Dao(),
                new Tracker_Rule_List_Dao(),
                $logger
            ),
            $logger
        );
    }

    private function getLogger(): \Psr\Log\LoggerInterface
    {
        return BackendLogger::getDefaultLogger("scaled_agile_syslog");
    }

    private function getPlanningAdapter(): PlanningAdapter
    {
        return new PlanningAdapter(\PlanningFactory::build());
    }
}