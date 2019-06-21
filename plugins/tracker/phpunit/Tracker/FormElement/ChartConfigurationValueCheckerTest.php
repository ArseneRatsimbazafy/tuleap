<?php
/**
 * Copyright (c) Enalean, 2017 - Present All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use TimePeriodWithoutWeekEnd;
use Tracker_FormElement_Chart_Field_Exception;

require_once __DIR__.'/../../bootstrap.php';

class ChartConfigurationValueCheckerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Tracker_FormElement_Field_Integer
     */
    public $duration_field;

    /**
     * @var \Tracker_Artifact_Changeset
     */
    private $new_changeset;

    /**
     * @var \Tracker_Artifact_ChangesetValue_Integer
     */
    private $duration_changeset;

    /**
     * @var \Tracker_Artifact_ChangesetValue_Date
     */
    private $start_date_changeset;

    /**
     * @var \PFUser
     */
    private $user;

    /**
     * @var \Tracker_Artifact
     */
    private $artifact;

    /**
     * @var \Tracker_FormElement_Field
     */
    private $start_date_field;

    /**
     * @var ChartConfigurationFieldRetriever
     */
    private $configuration_field_retriever;

    /**
     * @var ChartConfigurationValueRetriever
     */
    private $configuration_value_retriever;

    /**
     * @var ChartConfigurationValueChecker
     */
    private $chart_configuration_value_checker;

    /**
     * @var int
     */
    private $duration_value;

    /**
     * @var int
     */
    private $start_date_timestamp;

    protected function setUp() : void
    {
        parent::setUp();
        $this->configuration_field_retriever     = \Mockery::mock(\Tuleap\Tracker\FormElement\ChartConfigurationFieldRetriever::class);
        $this->configuration_value_retriever     = Mockery::mock(ChartConfigurationValueRetriever::class);
        $this->chart_configuration_value_checker = new ChartConfigurationValueChecker(
            $this->configuration_field_retriever,
            $this->configuration_value_retriever
        );

        $this->start_date_field     = \Mockery::mock(\Tracker_FormElement_Field_Date::class);
        $this->duration_field       = \Mockery::mock(\Tracker_FormElement_Field_Integer::class);
        $this->artifact             = \Mockery::mock(\Tracker_Artifact::class);
        $this->user                 = \Mockery::mock(\PFUser::class);
        $this->start_date_changeset = \Mockery::mock(\Tracker_Artifact_ChangesetValue_Date::class);
        $this->duration_changeset   = \Mockery::mock(\Tracker_Artifact_ChangesetValue_Integer::class);
        $this->new_changeset        = \Mockery::mock(\Tracker_Artifact_Changeset::class);

        $this->duration_value       = 10;
        $this->start_date_timestamp = 1488470204;
    }

    public function testItReturnsFalseWhenChartDontHaveAStartDateField()
    {
        $this->configuration_field_retriever->shouldReceive('getStartDateField')
            ->with($this->artifact, $this->user)
            ->andThrow(new Tracker_FormElement_Chart_Field_Exception());

        $this->expectException('Tracker_FormElement_Chart_Field_Exception');

        $this->assertFalse(
            $this->chart_configuration_value_checker->hasStartDate($this->artifact, $this->user)
        );
    }

    public function testItReturnsFalseWhenStartDateFieldIsNeverDefined()
    {
        $this->configuration_field_retriever->shouldReceive('getStartDateField')
            ->with($this->artifact, $this->user)
            ->andReturn($this->start_date_field);

        $this->artifact->shouldReceive('getValue')->with($this->start_date_field)->andReturnNull();

        $this->assertFalse(
            $this->chart_configuration_value_checker->hasStartDate($this->artifact, $this->user)
        );
    }

    public function testItReturnsFalseWhenStartDateFieldIsEmpty()
    {
        $this->configuration_field_retriever->shouldReceive('getStartDateField')
            ->with($this->artifact, $this->user)
            ->andReturn($this->start_date_field);

        $this->artifact->shouldReceive('getValue')
            ->with($this->start_date_field)
            ->andReturn($this->start_date_changeset);

        $this->start_date_changeset->shouldReceive('getTimestamp')->andReturnNull();

        $this->assertFalse(
            $this->chart_configuration_value_checker->hasStartDate($this->artifact, $this->user)
        );
    }

    public function testItReturnsTrueWhenChartHasAStartDateAndStartDateIsFiled()
    {
        $this->configuration_field_retriever->shouldReceive('getStartDateField')
            ->with($this->artifact, $this->user)
            ->andReturn($this->start_date_field);

        $this->artifact->shouldReceive('getValue')
            ->with($this->start_date_field)
            ->andReturn($this->start_date_changeset);

        $this->start_date_changeset->shouldReceive('getTimestamp')->andReturn($this->start_date_timestamp);

        $this->assertTrue(
            $this->chart_configuration_value_checker->hasStartDate($this->artifact, $this->user)
        );
    }

    public function testItReturnsConfigurationIsNotCorrectlySetWhenStartDateIsMissing()
    {
        $this->configuration_value_retriever->shouldReceive('getTimePeriod')
            ->with($this->artifact, $this->user)
            ->andReturn(new TimePeriodWithoutWeekEnd(null, $this->duration_value));

        $this->assertFalse(
            $this->chart_configuration_value_checker->areBurndownFieldsCorrectlySet($this->artifact, $this->user)
        );
    }

    public function testItReturnsConfigurationIsNotCorrectlySetWhenDurationIsMissing()
    {
        $this->configuration_value_retriever->shouldReceive('getTimePeriod')
            ->with($this->artifact, $this->user)
            ->andReturn(new TimePeriodWithoutWeekEnd($this->start_date_timestamp, null));

        $this->assertFalse(
            $this->chart_configuration_value_checker->areBurndownFieldsCorrectlySet($this->artifact, $this->user)
        );
    }

    public function testItReturnsConfigurationIsCorrectlySetWhenBurndownHasAStartDateAndADuration()
    {
        $this->configuration_value_retriever->shouldReceive('getTimePeriod')
            ->with($this->artifact, $this->user)
            ->andReturn(new TimePeriodWithoutWeekEnd($this->start_date_timestamp, $this->duration_value));

        $this->assertTrue(
            $this->chart_configuration_value_checker->areBurndownFieldsCorrectlySet($this->artifact, $this->user)
        );
    }

    public function testItReturnsFalseWhenStartDateAndDurationDontHaveChanged()
    {
        $this->configuration_field_retriever->shouldReceive('getDurationField')
            ->with($this->artifact, $this->user)
            ->andReturn($this->duration_field);

        $this->configuration_field_retriever->shouldReceive('getStartDateField')
            ->with($this->artifact, $this->user)
            ->andReturn($this->start_date_field);

        $this->new_changeset->shouldReceive('getValue')
            ->with($this->start_date_field)
            ->andReturn($this->start_date_changeset);

        $this->new_changeset->shouldReceive('getValue')
            ->with($this->duration_field)
            ->andReturnFalse();

        $this->start_date_changeset->shouldReceive('hasChanged')->andReturnFalse();
        $this->duration_changeset->shouldReceive('hasChanged')->andReturnFalse();

        $this->assertFalse(
            $this->chart_configuration_value_checker->hasConfigurationChange(
                $this->artifact,
                $this->user,
                $this->new_changeset
            )
        );
    }

    public function testItReturnsTrueWhenStartDateHaveChanged()
    {
        $this->configuration_field_retriever->shouldReceive('getDurationField')
            ->with($this->artifact, $this->user)
            ->andReturn($this->duration_field);

        $this->configuration_field_retriever->shouldReceive('getStartDateField')
            ->with($this->artifact, $this->user)
            ->andReturn($this->start_date_field);

        $this->new_changeset->shouldReceive('getValue')
            ->with($this->start_date_field)
            ->andReturn($this->start_date_changeset);

        $this->new_changeset->shouldReceive('getValue')
            ->with($this->duration_field)
            ->andReturn($this->duration_changeset);

        $this->start_date_changeset->shouldReceive('hasChanged')->andReturnTrue();
        $this->duration_changeset->shouldReceive('hasChanged')->andReturnFalse();

        $this->assertTrue(
            $this->chart_configuration_value_checker->hasConfigurationChange(
                $this->artifact,
                $this->user,
                $this->new_changeset
            )
        );
    }

    public function testItReturnsTrueWhenDurationHaveChanged()
    {
        $this->configuration_field_retriever->shouldReceive('getDurationField')
            ->with($this->artifact, $this->user)
            ->andReturn($this->duration_field);

        $this->configuration_field_retriever->shouldReceive('getStartDateField')
            ->with($this->artifact, $this->user)
            ->andReturn($this->start_date_field);

        $this->new_changeset->shouldReceive('getValue')
            ->with($this->start_date_field)
            ->andReturn($this->start_date_changeset);

        $this->new_changeset->shouldReceive('getValue')
            ->with($this->duration_field)
            ->andReturn($this->duration_changeset);

        $this->start_date_changeset->shouldReceive('hasChanged')->andReturnFalse();
        $this->duration_changeset->shouldReceive('hasChanged')->andReturnTrue();

        $this->assertTrue(
            $this->chart_configuration_value_checker->hasConfigurationChange(
                $this->artifact,
                $this->user,
                $this->new_changeset
            )
        );
    }
}
