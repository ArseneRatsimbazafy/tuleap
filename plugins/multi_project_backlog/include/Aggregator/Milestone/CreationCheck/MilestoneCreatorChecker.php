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

namespace Tuleap\MultiProjectBacklog\Aggregator\Milestone\CreationCheck;

use PFUser;
use Planning_VirtualTopMilestone;
use Psr\Log\LoggerInterface;
use Tuleap\MultiProjectBacklog\Aggregator\ContributorProjectsCollectionBuilder;
use Tuleap\MultiProjectBacklog\Aggregator\Milestone\MilestoneTrackerCollectionFactory;
use Tuleap\MultiProjectBacklog\Aggregator\Milestone\MilestoneTrackerRetrievalException;
use Tuleap\MultiProjectBacklog\Aggregator\Milestone\SynchronizedFieldCollectionBuilder;
use Tuleap\MultiProjectBacklog\Aggregator\Milestone\SynchronizedFieldRetrievalException;

class MilestoneCreatorChecker
{
    /**
     * @var ContributorProjectsCollectionBuilder
     */
    private $projects_builder;
    /**
     * @var MilestoneTrackerCollectionFactory
     */
    private $milestone_trackers_factory;
    /**
     * @var SynchronizedFieldCollectionBuilder
     */
    private $field_collection_builder;
    /**
     * @var SemanticChecker
     */
    private $semantic_checker;

    /**
     * @var RequiredFieldChecker
     */
    private $required_field_checker;
    /**
     * @var WorkflowChecker
     */
    private $workflow_checker;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        ContributorProjectsCollectionBuilder $contributor_projects_collection_builder,
        MilestoneTrackerCollectionFactory $milestone_trackers_factory,
        SynchronizedFieldCollectionBuilder $field_collection_builder,
        SemanticChecker $semantic_checker,
        RequiredFieldChecker $required_field_checker,
        WorkflowChecker $workflow_checker,
        LoggerInterface $logger
    ) {
        $this->projects_builder           = $contributor_projects_collection_builder;
        $this->milestone_trackers_factory = $milestone_trackers_factory;
        $this->field_collection_builder   = $field_collection_builder;
        $this->semantic_checker           = $semantic_checker;
        $this->required_field_checker     = $required_field_checker;
        $this->workflow_checker           = $workflow_checker;
        $this->logger                     = $logger;
    }

    public function canMilestoneBeCreated(Planning_VirtualTopMilestone $top_milestone, PFUser $user): bool
    {
        $this->logger->debug(
            "Checking if milestone can be created in top plannning of project " . $top_milestone->getProject()->getUnixName() .
            " by user " . $user->getName() . ' (#' . $user->getId() . ')'
        );

        $aggregator_project = $top_milestone->getProject();

        $contributor_projects_collection = $this->projects_builder->getContributorProjectForAGivenAggregatorProject(
            $aggregator_project
        );
        if ($contributor_projects_collection->isEmpty()) {
            $this->logger->debug("No contributor project found.");
            return true;
        }
        try {
            $milestone_tracker_collection = $this->milestone_trackers_factory->buildFromAggregatorProjectAndItsContributors(
                $aggregator_project,
                $contributor_projects_collection,
                $user
            );
            $contributor_milestones       = $this->milestone_trackers_factory->buildFromContributorProjects(
                $contributor_projects_collection,
                $user
            );
        } catch (MilestoneTrackerRetrievalException $exception) {
            $this->logger->error("Cannot retrieve all the milestones", ['exception' => $exception]);
            return false;
        }
        if (! $this->semantic_checker->areTrackerSemanticsWellConfigured($top_milestone, $milestone_tracker_collection)) {
            $this->logger->error("Semantics are not well configured.");
            return false;
        }
        if (! $contributor_milestones->canUserSubmitAnArtifactInAllTrackers($user)) {
            $this->logger->debug("User cannot submit an artifact in all contributor trackers.");
            return false;
        }

        try {
            $fields = $this->field_collection_builder->buildFromMilestoneTrackers($milestone_tracker_collection);
        } catch (SynchronizedFieldRetrievalException $exception) {
            $this->logger->error("Cannot retrieve all the synchronized fields", ['exception' => $exception]);
            return false;
        }
        if (! $fields->canUserSubmitAndUpdateAllFields($user)) {
            $this->logger->debug("User cannot submit and update all needed fields in all trackers.");
            return false;
        }

        if (
            ! $this->required_field_checker->areRequiredFieldsOfContributorTrackersLimitedToTheSynchronizedFields(
                $contributor_milestones,
                $fields
            )
        ) {
            $this->logger->debug("A contributor tracker has a required fields outside the synchronized fields.");
            return false;
        }

        if (
            ! $this->workflow_checker->areWorkflowsNotUsedWithSynchronizedFieldsInContributorTrackers(
                $contributor_milestones,
                $fields
            )
        ) {
            $this->logger->debug("A contributor tracker is using one of the synchronized fields in a workflow rule.");
            return false;
        }

        $this->logger->debug("User can create a milestone in the project.");
        return true;
    }
}
