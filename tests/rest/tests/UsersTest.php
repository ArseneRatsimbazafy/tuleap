<?php
/**
 * Copyright (c) Enalean, 2014 - 2018. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

use Test\Rest\TuleapConfig;

/**
 * @group UserGroupTests
 */
class UsersTest extends RestBase // phpcs:ignore
{

    /**
 * @var TuleapConfig
*/
    private $tuleap_config;

    public function __construct()
    {
        parent::__construct();
        $this->tuleap_config = TuleapConfig::instance();
        $this->tuleap_config->setForgeToAnonymous();
    }

    public function testGetIdAsAnonymousHasMinimalInformation()
    {
        $response = $this->client->get('users/'.$this->user_ids[REST_TestDataBuilder::TEST_USER_1_NAME])->send();
        $this->assertEquals($response->getStatusCode(), 200);

        $json = $response->json();
        $this->assertEquals($this->user_ids[REST_TestDataBuilder::TEST_USER_1_NAME], $json['id']);
        $this->assertEquals('users/'.$this->user_ids[REST_TestDataBuilder::TEST_USER_1_NAME], $json['uri']);
        $this->assertEquals(REST_TestDataBuilder::TEST_USER_1_REALNAME, $json['real_name']);
        $this->assertEquals(REST_TestDataBuilder::TEST_USER_1_NAME, $json['username']);
        $this->assertEquals(REST_TestDataBuilder::TEST_USER_1_LDAPID, $json['ldap_id']);
        $this->assertEquals('https://localhost/themes/common/images/avatar_default.png', $json['avatar_url']);
        $this->assertFalse(isset($json['email']));
        $this->assertFalse(isset($json['status']));
    }

    public function testGETIdAsRegularUser()
    {
        $response = $this->getResponseByName(REST_TestDataBuilder::TEST_USER_1_NAME, $this->client->get('users/'.$this->user_ids[REST_TestDataBuilder::TEST_USER_1_NAME]));
        $this->assertEquals($response->getStatusCode(), 200);

        $json = $response->json();
        $this->assertEquals($this->user_ids[REST_TestDataBuilder::TEST_USER_1_NAME], $json['id']);
        $this->assertEquals('users/'.$this->user_ids[REST_TestDataBuilder::TEST_USER_1_NAME], $json['uri']);
        $this->assertEquals(REST_TestDataBuilder::TEST_USER_1_EMAIL, $json['email']);
        $this->assertEquals(REST_TestDataBuilder::TEST_USER_1_REALNAME, $json['real_name']);
        $this->assertEquals(REST_TestDataBuilder::TEST_USER_1_NAME, $json['username']);
        $this->assertEquals(REST_TestDataBuilder::TEST_USER_1_LDAPID, $json['ldap_id']);
        $this->assertEquals('https://localhost/themes/common/images/avatar_default.png', $json['avatar_url']);
    }

    public function testGETIdDoesNotWorkIfUserDoesNotExist()
    {
        $response = $this->getResponseByName(REST_TestDataBuilder::TEST_USER_1_NAME, $this->client->get('users/1'));
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testGETMembershipBySelfReturnsUserGroups()
    {
        $response = $this->getResponseByName(REST_TestDataBuilder::TEST_USER_2_NAME, $this->client->get('users/'.$this->user_ids[REST_TestDataBuilder::TEST_USER_2_NAME].'/membership'));
        $this->assertEquals($response->getStatusCode(), 200);

        $json = $response->json();
        $this->assertCount(3, $json);
        $this->assertContains('site_active', $json);
        $this->assertContains('private-member_project_members', $json);
        $this->assertContains('ug_102', $json);
    }

    public function testUserCannotSeeGroupOfAnotherUser()
    {
        $response = $this->getResponseByName(
            REST_TestDataBuilder::TEST_USER_1_NAME,
            $this->client->get('users/'.$this->user_ids[REST_TestDataBuilder::TEST_USER_2_NAME].'/membership')
        );
        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testUserCanSeeGroupOfAnotherUserIfSheHasDelegatedPermissions()
    {
        $response = $this->getResponseByName(REST_TestDataBuilder::TEST_USER_3_NAME, $this->client->get('users/'.$this->user_ids[REST_TestDataBuilder::TEST_USER_2_NAME].'/membership'));
        $this->assertEquals($response->getStatusCode(), 200);

        $json = $response->json();
        $this->assertCount(3, $json);
        $this->assertContains('site_active', $json);
        $this->assertContains('private-member_project_members', $json);
        $this->assertContains('ug_102', $json);
    }

    public function testUserCanUpdateAnotherUserIfSheHasDelegatedPermissions()
    {
        $value = json_encode(
            array(
            'values' => array(
                    'status' => "R",
            )
            )
        );
        $response = $this->getResponseByName(REST_TestDataBuilder::ADMIN_USER_NAME, $this->client->patch('users/'.$this->user_ids[REST_TestDataBuilder::TEST_USER_2_NAME], null, $value));
        $this->assertEquals($response->getStatusCode(), 200);

        $response = $this->getResponseByName(REST_TestDataBuilder::ADMIN_USER_NAME, $this->client->get('users/'.$this->user_ids[REST_TestDataBuilder::TEST_USER_2_NAME]));
        $this->assertEquals($response->getStatusCode(), 200);
        $json = $response->json();
        $this->assertEquals($this->user_ids[REST_TestDataBuilder::TEST_USER_2_NAME], $json['id']);
        $this->assertEquals('users/'.$this->user_ids[REST_TestDataBuilder::TEST_USER_2_NAME], $json['uri']);
        $this->assertEquals("R", $json['status']);

        $value = json_encode(
            array(
            'values' => array(
                    'status' => "A",
            )
            )
        );
        $response = $this->getResponseByName(REST_TestDataBuilder::ADMIN_USER_NAME, $this->client->patch('users/'.$this->user_ids[REST_TestDataBuilder::TEST_USER_2_NAME], null, $value));
        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testSiteAdminCanSeeGroupOfAnyUser()
    {
        $response = $this->getResponseByName(REST_TestDataBuilder::ADMIN_USER_NAME, $this->client->get('users/'.$this->user_ids[REST_TestDataBuilder::TEST_USER_2_NAME].'/membership'));
        $this->assertEquals($response->getStatusCode(), 200);

        $json = $response->json();
        $this->assertCount(3, $json);
    }

    public function testInRestrictedForgeThatActiveProjectMemberIsMemberOfStaticUgroup()
    {
        $this->tuleap_config->setForgeToRestricted();

        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->get('users/'.$this->user_ids[REST_TestDataBuilder::TEST_USER_1_NAME].'/membership')
        );

        $ugroups = $response->json();
        $this->assertContains('ug_'. REST_TestDataBuilder::STATIC_PRIVATE_MEMBER_UGROUP_DEVS_ID, $ugroups);
        $this->assertContains('ug_'. REST_TestDataBuilder::STATIC_PUBLIC_MEMBER_UGROUP_DEVS_ID, $ugroups);
        $this->assertContains('ug_'. REST_TestDataBuilder::STATIC_PUBLIC_INCL_RESTRICTED_UGROUP_DEVS_ID, $ugroups);

        $this->tuleap_config->setForgeToAnonymous();
    }

    public function testInRestrictedForgeThatRestrictedProjectMemberIsMemberOfStaticUgroup()
    {
        $this->tuleap_config->setForgeToRestricted();

        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->get('users/'.$this->user_ids[REST_TestDataBuilder::TEST_USER_RESTRICTED_1_NAME].'/membership')
        );

        $ugroups = $response->json();
        $this->assertContains('ug_'. REST_TestDataBuilder::STATIC_PRIVATE_MEMBER_UGROUP_DEVS_ID, $ugroups);
        $this->assertContains('ug_'. REST_TestDataBuilder::STATIC_PUBLIC_MEMBER_UGROUP_DEVS_ID, $ugroups);
        $this->assertContains('ug_'. REST_TestDataBuilder::STATIC_PUBLIC_INCL_RESTRICTED_UGROUP_DEVS_ID, $ugroups);

        $this->tuleap_config->setForgeToAnonymous();
    }

    public function testInRestrictedForgeThatRestrictedNotProjectMemberIsOnlyMemberOfStaticUgroupInPublicInclRestricted()
    {
        $this->tuleap_config->setForgeToRestricted();

        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->get('users/'.$this->user_ids[REST_TestDataBuilder::TEST_USER_RESTRICTED_2_NAME].'/membership')
        );

        $ugroups = $response->json();
        $this->assertNotContains('ug_'. REST_TestDataBuilder::STATIC_PRIVATE_MEMBER_UGROUP_DEVS_ID, $ugroups);
        $this->assertNotContains('ug_'. REST_TestDataBuilder::STATIC_PUBLIC_MEMBER_UGROUP_DEVS_ID, $ugroups);
        $this->assertContains('ug_'. REST_TestDataBuilder::STATIC_PUBLIC_INCL_RESTRICTED_UGROUP_DEVS_ID, $ugroups);

        $this->tuleap_config->setForgeToAnonymous();
    }

    public function testInRestrictedForgeThatActiveNotProjectMemberIsNotMemberOfStaticUgroup()
    {
        $this->tuleap_config->setForgeToRestricted();

        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->get('users/'.$this->user_ids[REST_TestDataBuilder::TEST_USER_4_NAME].'/membership')
        );

        $ugroups = $response->json();
        $this->assertNotContains('ug_'. REST_TestDataBuilder::STATIC_PRIVATE_MEMBER_UGROUP_DEVS_ID, $ugroups);
        $this->assertNotContains('ug_'. REST_TestDataBuilder::STATIC_PUBLIC_MEMBER_UGROUP_DEVS_ID, $ugroups);
        $this->assertNotContains('ug_'. REST_TestDataBuilder::STATIC_PUBLIC_INCL_RESTRICTED_UGROUP_DEVS_ID, $ugroups);

        $this->tuleap_config->setForgeToAnonymous();
    }

    public function testInRestrictedForgeThatActiveNotProjectMemberIsMemberOfStaticUgroupExceptPrivateProjects()
    {
        $this->tuleap_config->setForgeToRestricted();

        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->get('users/'.$this->user_ids[REST_TestDataBuilder::TEST_USER_5_NAME].'/membership')
        );

        $ugroups = $response->json();
        $this->assertNotContains('ug_'. REST_TestDataBuilder::STATIC_PRIVATE_MEMBER_UGROUP_DEVS_ID, $ugroups);
        $this->assertContains('ug_'. REST_TestDataBuilder::STATIC_PUBLIC_MEMBER_UGROUP_DEVS_ID, $ugroups);
        $this->assertContains('ug_'. REST_TestDataBuilder::STATIC_PUBLIC_INCL_RESTRICTED_UGROUP_DEVS_ID, $ugroups);

        $this->tuleap_config->setForgeToAnonymous();
    }

    public function testInAnonymousForgeThatActiveProjectMemberIsMemberOfStaticUgroup()
    {
        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->get('users/'.$this->user_ids[REST_TestDataBuilder::TEST_USER_1_NAME].'/membership')
        );

        $ugroups = $response->json();
        $this->assertContains('ug_'. REST_TestDataBuilder::STATIC_PRIVATE_MEMBER_UGROUP_DEVS_ID, $ugroups);
        $this->assertContains('ug_'. REST_TestDataBuilder::STATIC_PUBLIC_MEMBER_UGROUP_DEVS_ID, $ugroups);
    }


    public function testInAnonymousForgeThatActiveNotProjectMemberIsNotMemberOfStaticUgroup()
    {
        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->get('users/'.$this->user_ids[REST_TestDataBuilder::TEST_USER_4_NAME].'/membership')
        );

        $ugroups = $response->json();
        $this->assertNotContains('ug_'. REST_TestDataBuilder::STATIC_PRIVATE_MEMBER_UGROUP_DEVS_ID, $ugroups);
        $this->assertNotContains('ug_'. REST_TestDataBuilder::STATIC_PUBLIC_MEMBER_UGROUP_DEVS_ID, $ugroups);
    }

    public function testInAnonymousForgeThatActiveNotProjectMemberIsMemberOfStaticUgroupExceptPrivateProjects()
    {
        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->get('users/'.$this->user_ids[REST_TestDataBuilder::TEST_USER_5_NAME].'/membership')
        );

        $ugroups = $response->json();
        $this->assertNotContains('ug_'. REST_TestDataBuilder::STATIC_PRIVATE_MEMBER_UGROUP_DEVS_ID, $ugroups);
        $this->assertContains('ug_'. REST_TestDataBuilder::STATIC_PUBLIC_MEMBER_UGROUP_DEVS_ID, $ugroups);
    }

    public function testInRegularForgeThatActiveProjectMemberIsMemberOfStaticUgroup()
    {
        $this->tuleap_config->setForgeToRegular();

        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->get('users/'.$this->user_ids[REST_TestDataBuilder::TEST_USER_1_NAME].'/membership')
        );

        $ugroups = $response->json();
        $this->assertContains('ug_'. REST_TestDataBuilder::STATIC_PRIVATE_MEMBER_UGROUP_DEVS_ID, $ugroups);
        $this->assertContains('ug_'. REST_TestDataBuilder::STATIC_PUBLIC_MEMBER_UGROUP_DEVS_ID, $ugroups);

        $this->tuleap_config->setForgeToAnonymous();
    }


    public function testInRegularForgeThatActiveNotProjectMemberIsNotMemberOfStaticUgroup()
    {
        $this->tuleap_config->setForgeToRegular();

        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->get('users/'.$this->user_ids[REST_TestDataBuilder::TEST_USER_4_NAME].'/membership')
        );

        $ugroups = $response->json();
        $this->assertNotContains('ug_'. REST_TestDataBuilder::STATIC_PRIVATE_MEMBER_UGROUP_DEVS_ID, $ugroups);
        $this->assertNotContains('ug_'. REST_TestDataBuilder::STATIC_PUBLIC_MEMBER_UGROUP_DEVS_ID, $ugroups);

        $this->tuleap_config->setForgeToAnonymous();
    }

    public function testInRegularForgeThatActiveNotProjectMemberIsMemberOfStaticUgroupExceptPrivateProjects()
    {
        $this->tuleap_config->setForgeToRegular();

        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->get('users/'.$this->user_ids[REST_TestDataBuilder::TEST_USER_5_NAME].'/membership')
        );

        $ugroups = $response->json();
        $this->assertNotContains('ug_'. REST_TestDataBuilder::STATIC_PRIVATE_MEMBER_UGROUP_DEVS_ID, $ugroups);
        $this->assertContains('ug_'. REST_TestDataBuilder::STATIC_PUBLIC_MEMBER_UGROUP_DEVS_ID, $ugroups);

        $this->tuleap_config->setForgeToAnonymous();
    }

    public function testGetUsersWithMatching()
    {
        $response = $this->getResponseByName(REST_TestDataBuilder::TEST_USER_1_NAME, $this->client->get('users?query=rest_api_tester&limit=10'));
        $this->assertEquals($response->getStatusCode(), 200);

        $json = $response->json();
        $this->assertCount(5, $json);
    }

    public function testGetUserWithExactSearch()
    {
        $search = urlencode(
            json_encode(
                array(
                    'username' => REST_TestDataBuilder::TEST_USER_1_NAME
                )
            )
        );

        $response = $this->getResponseByName(REST_TestDataBuilder::TEST_USER_1_NAME, $this->client->get("users?query=$search&limit=10"));
        $this->assertEquals($response->getStatusCode(), 200);

        $json = $response->json();
        $this->assertCount(1, $json);
        $this->assertEquals($this->user_ids[REST_TestDataBuilder::TEST_USER_1_NAME], $json[0]['id']);
    }

    public function testGetUserWithExactSearchWithoutResult()
    {
        $search = urlencode(
            json_encode(
                array(
                    'username' => 'muppet'
                )
            )
        );

        $response = $this->getResponseByName(REST_TestDataBuilder::TEST_USER_1_NAME, $this->client->get("users?query=$search&limit=10"));
        $this->assertEquals($response->getStatusCode(), 200);

        $json = $response->json();
        $this->assertCount(0, $json);
    }

    public function testGetUserWithInvalidJson()
    {
        $search = urlencode('{jeanclaude}');

        $response = $this->getResponseByName(REST_TestDataBuilder::TEST_USER_1_NAME, $this->client->get("users?query=$search&limit=10"));
        $this->assertEquals($response->getStatusCode(), 400);
    }

    public function testOptionsPreferences()
    {
        $response = $this->getResponseByName(REST_TestDataBuilder::TEST_USER_1_NAME, $this->client->options('users/'.$this->user_ids[REST_TestDataBuilder::TEST_USER_1_NAME].'/preferences'));

        $this->assertEquals(array('OPTIONS', 'GET', 'PATCH', 'DELETE'), $response->getHeader('Allow')->normalize()->toArray());
        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testPatchPreferences()
    {
        $preference = json_encode(
            array(
                'key'   => 'my_preference',
                'value' => 'my_preference_value'
            )
        );

        $response = $this->getResponseByName(REST_TestDataBuilder::TEST_USER_1_NAME, $this->client->patch('users/'.$this->user_ids[REST_TestDataBuilder::TEST_USER_1_NAME].'/preferences', null, $preference));
        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testPatchPreferencesAnotherUser()
    {
        $preference = json_encode(
            array(
                'key'   => 'my_preference',
                'value' => 'my_preference_value'
            )
        );

        $response = $this->getResponseByName(REST_TestDataBuilder::TEST_USER_1_NAME, $this->client->patch('users/'.$this->user_ids[REST_TestDataBuilder::TEST_USER_2_NAME].'/preferences', null, $preference));
        $this->assertEquals($response->getStatusCode(), 403);
    }

    public function _testGETPreferences()
    {
        $response = $this->getResponseByName(REST_TestDataBuilder::TEST_USER_1_NAME, $this->client->get('users/'.$this->user_ids[REST_TestDataBuilder::TEST_USER_1_NAME].'/preferences?key=my_preference'));

        $this->assertEquals($response->getStatusCode(), 200);

        $json = $response->json();
        $this->assertEquals('my_preference', $json['key']);
        $this->assertEquals('my_preference_value', $json['value']);
    }

    public function testDeletePreferences()
    {
        $preference = json_encode(
            array(
                'key'   => 'preference_to_be_deleted',
                'value' => 'awesome_value'
            )
        );

        $response = $this->getResponseByName(REST_TestDataBuilder::TEST_USER_1_NAME, $this->client->patch('users/'.$this->user_ids[REST_TestDataBuilder::TEST_USER_1_NAME].'/preferences', null, $preference));
        $this->assertEquals($response->getStatusCode(), 200);

        $response = $this->getResponseByName(REST_TestDataBuilder::TEST_USER_1_NAME, $this->client->delete('users/'.$this->user_ids[REST_TestDataBuilder::TEST_USER_1_NAME].'/preferences?key=preference_to_be_deleted'));
        $this->assertEquals($response->getStatusCode(), 200);

        $response = $this->getResponseByName(REST_TestDataBuilder::TEST_USER_1_NAME, $this->client->get('users/'.$this->user_ids[REST_TestDataBuilder::TEST_USER_1_NAME].'/preferences?key=preference_to_be_deleted'));
        $this->assertEquals($response->getStatusCode(), 200);

        $json = $response->json();
        $this->assertEquals('preference_to_be_deleted', $json['key']);
        $this->assertEquals(false, $json['value']);
    }

    public function testGETPreferencesAnotherUser()
    {
        $response = $this->getResponseByName(
            REST_TestDataBuilder::TEST_USER_1_NAME,
            $this->client->get('users/'.$this->user_ids[REST_TestDataBuilder::TEST_USER_2_NAME].'/preferences?key=my_preference')
        );
        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testGETHistoryAnotherUser()
    {
        $response = $this->getResponseByName(
            REST_TestDataBuilder::TEST_USER_1_NAME,
            $this->client->get('users/'.$this->user_ids[REST_TestDataBuilder::TEST_USER_2_NAME].'/history')
        );
        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testPUTHistoryAnotherUser()
    {
        $response = $this->getResponseByName(
            REST_TestDataBuilder::TEST_USER_1_NAME,
            $this->client->put('users/'.$this->user_ids[REST_TestDataBuilder::TEST_USER_2_NAME].'/history', null, json_encode(array()))
        );
        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testPUTHistoryManipulation()
    {
        $history_entries  = json_encode(
            array (
                array (
                    'visit_time' => 1496386853,
                    'xref' => 'bugs #845',
                    'link' => '/plugins/tracker/?aid=845',
                    'title' => '',
                )
            )
        );

        $response = $this->getResponseByName(
            REST_TestDataBuilder::TEST_USER_1_NAME,
            $this->client->put('users/'.$this->user_ids[REST_TestDataBuilder::TEST_USER_2_NAME].'/history', null, $history_entries)
        );
        $this->assertEquals(403, $response->getStatusCode());
    }
}
