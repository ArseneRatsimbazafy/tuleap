<?php
/**
 * Copyright (c) Enalean, 2014. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */
namespace Testing;

use TestingDataBuilder;

require_once dirname(__FILE__).'/../bootstrap.php';

/**
 * @group ArtifactsTest
 */
class ProjectTest extends BaseTest {

    public function testGetCampaigns() {

        $response  = $this->getResponse($this->client->get('projects/'.TestingDataBuilder::PROJECT_TEST_MGMT_ID.'/testing_campaigns'));
        $campaigns = $response->json();

        $this->assertCount(3, $campaigns);

        $first_campaign = $campaigns[0];
        $this->assertArrayHasKey('id', $first_campaign);
        $this->assertEquals($first_campaign['label'], 'Tuleap 7.3');
        $this->assertEquals($first_campaign['status'], 'Open');

        $second_campaign = $campaigns[1];
        $this->assertArrayHasKey('id', $second_campaign);
        $this->assertEquals($second_campaign['label'], 'Tuleap 7.2');
        $this->assertEquals($second_campaign['status'], 'Closed');

        $third_campaign = $campaigns[2];
        $this->assertArrayHasKey('id', $third_campaign);
        $this->assertEquals($third_campaign['label'], 'Tuleap 7.1');
        $this->assertEquals($third_campaign['status'], 'Closed');
    }

    public function testStatusOfExecutionsAreCorrect() {

        $response  = $this->getResponse($this->client->get('projects/'.TestingDataBuilder::PROJECT_TEST_MGMT_ID.'/testing_campaigns'));
        $campaigns = $response->json();

        $first_campaign = $campaigns[0];
        $this->assertArrayHasKey('nb_of_not_run', $first_campaign);
        $this->assertEquals($first_campaign['nb_of_not_run'], 0);

        $this->assertArrayHasKey('nb_of_passed', $first_campaign);
        $this->assertEquals($first_campaign['nb_of_passed'], 2);

        $this->assertArrayHasKey('nb_of_failed', $first_campaign);
        $this->assertEquals($first_campaign['nb_of_failed'], 1);

        $this->assertArrayHasKey('nb_of_blocked', $first_campaign);
        $this->assertEquals($first_campaign['nb_of_blocked'], 0);
    }

    public function testGetDefinitions() {

        $response    = $this->getResponse($this->client->get('projects/'.TestingDataBuilder::PROJECT_TEST_MGMT_ID.'/testing_definitions'));
        $definitions = $response->json();

        $this->assertEquals(sizeof($definitions), 3);
    }

    public function testGetEnvironments() {

        $response = $this->getResponse($this->client->get('projects/'.TestingDataBuilder::PROJECT_TEST_MGMT_ID.'/testing_environments'));
        $environments = $response->json();

        $this->assertArrayHasKey(0, $environments);
        $this->assertEquals($environments[0]['label'], 'CentOS 5 - PHP 5.1');

        $this->assertArrayHasKey(1, $environments);
        $this->assertEquals($environments[1]['label'], 'CentOS 5 - PHP 5.3');

        $this->assertArrayHasKey(2, $environments);
        $this->assertEquals($environments[2]['label'], 'CentOS 6 - PHP 5.3');

        $this->assertTrue(true);
    }
}