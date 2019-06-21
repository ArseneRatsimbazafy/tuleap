<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\FormElement;

use Mockery;
use Mockery\MockInterface;
use Tuleap\Tracker\FormElement\ChartCachedDaysComparator;
use Tuleap\Tracker\FormElement\ChartConfigurationValueChecker;

require_once __DIR__ . '/../../../bootstrap.php';

class BurnupCacheCheckerTest extends \TuleapTestCase
{
    /**
     * @var ChartCachedDaysComparator|MockInterface
     */
    private $cache_days_comparator;
    /**
     * @var BurnupCacheGenerator|MockInterface
     */
    private $cache_generator;
    /**
     * @var \PFUser
     */
    private $user;
    /**
     * @var \Tracker_Artifact
     */
    private $artifact;
    /**
     * @var \TimePeriodWithoutWeekEnd
     */
    private $time_period;
    /**
     * @var BurnupCacheChecker
     */
    private $burnup_cache_Checker;
    /**
     * @var ChartConfigurationValueChecker
     */
    private $chart_value_checker;

    public function setUp()
    {
        parent::setUp();

        $this->cache_generator       = Mockery::spy(BurnupCacheGenerator::class);
        $this->chart_value_checker   = mock(ChartConfigurationValueChecker::class);
        $burnup_cache_dao            = mock(BurnupCacheDao::class);
        $this->cache_days_comparator = Mockery::mock(ChartCachedDaysComparator::class);
        $this->burnup_cache_Checker  = new BurnupCacheChecker(
            $this->cache_generator,
            $this->chart_value_checker,
            $burnup_cache_dao,
            $this->cache_days_comparator
        );

        $this->artifact = aMockArtifact()->withId(101)->build();

        $start_date        = new \DateTime();
        $duration          = 10;
        $this->time_period = new \TimePeriodWithoutWeekEnd($start_date->getTimestamp(), $duration);

        $this->user = aUser()->withId(101)->build();
    }

    public function itReturnsFalseWhenStartDateFieldIsNotReadable()
    {
        stub($this->chart_value_checker)->hasStartDate()->returns(false);

        $this->assertFalse(
            $this->burnup_cache_Checker->isBurnupUnderCalculation($this->artifact, $this->time_period, $this->user)
        );
    }

    public function itReturnsTrueWhenBurnupIsAlreadyUnderCalculation()
    {
        stub($this->chart_value_checker)->hasStartDate()->returns(true);
        $this->cache_generator->shouldReceive('isCacheBurnupAlreadyAsked')->with($this->artifact)->andReturn(true);
        $this->cache_days_comparator->shouldReceive('isNumberOfCachedDaysExpected')->andReturn(false);

        $this->assertTrue(
            $this->burnup_cache_Checker->isBurnupUnderCalculation($this->artifact, $this->time_period, $this->user)
        );
    }

    public function itReturnsTrueAndSendAnEventWhenCacheIsIncompleteForBurnup()
    {
        stub($this->chart_value_checker)->hasStartDate()->returns(true);
        $this->cache_generator->shouldReceive('isCacheBurnupAlreadyAsked')->with($this->artifact)->andReturn(false);
        $this->cache_days_comparator->shouldReceive('isNumberOfCachedDaysExpected')->andReturn(false);

        $this->cache_generator->shouldReceive('forceBurnupCacheGeneration')->once();
        $this->assertTrue(
            $this->burnup_cache_Checker->isBurnupUnderCalculation($this->artifact, $this->time_period, $this->user)
        );
    }

    public function itReturnsFalseWhenBurnupHasNoNeedToBeComputed()
    {
        stub($this->chart_value_checker)->hasStartDate()->returns(true);
        $this->cache_generator->shouldReceive('isCacheBurnupAlreadyAsked')->with($this->artifact)->andReturn(false);
        $this->cache_days_comparator->shouldReceive('isNumberOfCachedDaysExpected')->andReturn(true);

        $this->cache_generator->shouldReceive('forceBurnupCacheGeneration')->with($this->artifact->getId())->never();
        $this->assertFalse(
            $this->burnup_cache_Checker->isBurnupUnderCalculation($this->artifact, $this->time_period, $this->user)
        );
    }
}
