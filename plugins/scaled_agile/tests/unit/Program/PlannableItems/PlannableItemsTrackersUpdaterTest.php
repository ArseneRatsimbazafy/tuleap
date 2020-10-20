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

namespace Tuleap\ScaledAgile\Program\PlannableItems;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Planning;
use PlanningFactory;
use Tuleap\ScaledAgile\Team\TeamDao;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;

class PlannableItemsTrackersUpdaterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var PlannableItemsTrackersUpdater
     */
    private $updater;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|TeamDao
     */
    private $team_dao;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PlannableItemsTrackersDao
     */
    private $plannable_items_trackers_dao;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PlanningFactory
     */
    private $planning_factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->team_dao              = Mockery::mock(TeamDao::class);
        $this->plannable_items_trackers_dao = Mockery::mock(PlannableItemsTrackersDao::class);
        $this->planning_factory             = Mockery::mock(PlanningFactory::class);

        $this->updater = new PlannableItemsTrackersUpdater(
            $this->team_dao,
            $this->plannable_items_trackers_dao,
            $this->planning_factory,
            new DBTransactionExecutorPassthrough()
        );
    }

    public function testItUpdatesThePlannableItemsTrackers(): void
    {
        $program_top_planning = new Planning(1, 'Release Planning', 104, 'Release Backlog', 'Sprint Plan', []);
        $updated_planning        = new Planning(3, 'Release Planning', 105, 'Release Backlog', 'Sprint Plan', [302, 504]);

        $user = UserTestBuilder::aUser()->build();

        $this->team_dao->shouldReceive('isProjectATeamProject')
            ->with(105)
            ->once()
            ->andReturnTrue();

        $this->team_dao->shouldReceive('getProgramProjectsOfAGivenTeamProject')
            ->with(105)
            ->once()
            ->andReturn([
                ['program_project_id' => 102]
            ]);

        $this->plannable_items_trackers_dao->shouldReceive('deletePlannableItemsTrackerIdsOfAGivenTeamProject')
            ->once();

        $this->planning_factory->shouldReceive('getRootPlanning')
            ->once()
            ->with($user, 102)
            ->andReturn($program_top_planning);

        $this->plannable_items_trackers_dao->shouldReceive('addPlannableItemsTrackerIds')
            ->with(
                1,
                [302, 504]
            )
            ->once();

        $this->updater->updatePlannableItemsTrackersFromPlanning($updated_planning, $user);
    }

    public function testItDoesNothingIfThePlanningIsNotInATeamProject(): void
    {
        $updated_planning = new Planning(3, 'Release Planning', 105, 'Release Backlog', 'Sprint Plan', [302, 504]);
        $user             = UserTestBuilder::aUser()->build();

        $this->team_dao->shouldReceive('isProjectATeamProject')
            ->with(105)
            ->once()
            ->andReturnFalse();

        $this->plannable_items_trackers_dao->shouldNotReceive('deletePlannableItemsTrackerIdsOfAGivenTeamProject');
        $this->team_dao->shouldNotReceive('getProgramProjectsOfAGivenTeamProject');
        $this->plannable_items_trackers_dao->shouldNotReceive('addPlannableItemsTrackerIds');

        $this->updater->updatePlannableItemsTrackersFromPlanning($updated_planning, $user);
    }

    public function testItThrowsAnExceptionIfThereIsNotTopPlanningInProgramProject(): void
    {
        $updated_planning = new Planning(3, 'Release Planning', 105, 'Release Backlog', 'Sprint Plan', [302, 504]);
        $user             = UserTestBuilder::aUser()->build();

        $this->team_dao->shouldReceive('isProjectATeamProject')
            ->with(105)
            ->once()
            ->andReturnTrue();

        $this->team_dao->shouldReceive('getProgramProjectsOfAGivenTeamProject')
            ->with(105)
            ->once()
            ->andReturn([
                ['program_project_id' => 102]
            ]);

        $this->planning_factory->shouldReceive('getRootPlanning')
            ->once()
            ->with($user, 102)
            ->andReturnFalse();

        $this->plannable_items_trackers_dao->shouldNotReceive('deletePlannableItemsTrackerIdsOfAGivenTeamProject');
        $this->plannable_items_trackers_dao->shouldNotReceive('addPlannableItemsTrackerIds');

        $this->expectException(TopPlanningNotFoundInProgramProjectException::class);

        $this->updater->updatePlannableItemsTrackersFromPlanning($updated_planning, $user);
    }
}
