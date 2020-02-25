<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\TestManagement\XML;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Project;
use SimpleXMLElement;
use Tuleap\Project\UGroupRetrieverWithLegacy;
use Tuleap\TestManagement\Step\Definition\Field\StepDefinition;
use Tuleap\Tracker\XML\TrackerXmlImportFeedbackCollector;
use XML_RNGValidator;

final class TrackerXMLImportTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var ImportXMLFromTracker
     */
    private $xml_validator;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|UGroupRetrieverWithLegacy
     */
    private $ugroup_retriever;

    public function setUp(): void
    {
        $this->ugroup_retriever = Mockery::mock(UGroupRetrieverWithLegacy::class);
        $this->xml_validator = new ImportXMLFromTracker(new XML_RNGValidator(), $this->ugroup_retriever);
    }

    public function testValidateXMLImportThrowExceptionIfNotValidXML(): void
    {
        $xml_input = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
             <externalField type="ttmstepdef" ID="F1602" rank="2">
                  <name>steps</name>
                  <description><![CDATA[Definition of the test\'s steps]]></description>
                 <permissions>
                    <permission scope="field" REF="F1602" ugroup="UGROUP_ANONYMOUS" type="PLUGIN_TRACKER_FIELD_READ"/>
                    <permission scope="field" REF="F1602" ugroup="UGROUP_REGISTERED" type="PLUGIN_TRACKER_FIELD_SUBMIT"/>
                    <permission scope="field" REF="F1602" ugroup="UGROUP_PROJECT_MEMBERS" type="PLUGIN_TRACKER_FIELD_UPDATE"/>
                 </permissions>
             </externalField>'
        );

        $this->expectException('XML_ParseException');
        $this->xml_validator->validateXMLImport($xml_input);
    }

    public function testValidateXMLImportNotThrowException(): void
    {
        $xml_input = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
             <externalField type="ttmstepdef" ID="F1602" rank="2">
	            <name>steps</name>
	            <label>Steps definition</label>
	            <description>Definition</description>
            </externalField>'
        );

        $this->xml_validator->validateXMLImport($xml_input);
        $this->addToAssertionCount(1);
    }

    public function testGetInstanceFromXML()
    {
        $xml_input = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
             <externalField type="ttmstepdef" ID="F1602" rank="2">
                  <name>steps</name>
                  <label>Steps definition</label>
                  <description>Definition of the test\'s steps</description>
             </externalField>'
        );

        $feedback_collector = Mockery::mock(TrackerXmlImportFeedbackCollector::class);
        $feedback_collector->shouldReceive('addWarnings');

        $project     = Mockery::mock(Project::class);

        $step_def = new StepDefinition(
            0,
            0,
            0,
            "steps",
            "Steps definition",
            "Definition of the test's steps",
            1,
            'P',
            0,
            0,
            2,
            null
        );

        $result = $this->xml_validator->getInstanceFromXML($xml_input, $project, $feedback_collector);

        $this->assertEquals($step_def, $result);
    }
}
