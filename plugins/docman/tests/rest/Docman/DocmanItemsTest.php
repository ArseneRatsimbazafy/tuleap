<?php
/**
 * Copyright (c) Enalean, 2018-2019. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 *
 *
 */

namespace Tuleap\Docman\rest\v1;

use Guzzle\Http\Client;
use Tuleap\Docman\rest\DocmanBase;
use Tuleap\Docman\rest\DocmanDataBuilder;

require_once __DIR__ . '/../bootstrap.php';

class DocmanItemsTest extends DocmanBase
{

    public function testGetRootId()
    {
        $project_response = $this->getResponse($this->client->get('projects/' . $this->project_id));

        $this->assertSame(200, $project_response->getStatusCode());

        $json_projects = $project_response->json();
        return $json_projects['additional_informations']['docman']['root_item']['id'];
    }

    /**
     * @depends testGetRootId
     */
    public function testGetDocumentItemsForRegularUser($root_id)
    {
        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->get('docman_items/' . $root_id . '/docman_items')
        );
        $folder = $response->json();

        $this->assertEquals(count($folder), 1);
        $folder_id = $folder[0]['id'];
        $this->assertEquals($folder[0]['user_can_write'], true);

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->get('docman_items/' . $folder_id . '/docman_items')
        );
        $items = $response->json();

        $this->assertEquals(count($items), 6);

        $this->assertEquals($items[0]['title'], 'folder 2');
        $this->assertEquals($items[1]['title'], 'item A');
        $this->assertEquals($items[2]['title'], 'item C');
        $this->assertEquals($items[3]['title'], 'item E');
        $this->assertEquals($items[4]['title'], 'item F');
        $this->assertEquals($items[5]['title'], 'item G');

        $this->assertEquals('Test User 1 (rest_api_tester_1)', $items[0]['owner']['display_name']);
        $this->assertEquals('Anonymous user', $items[1]['owner']['display_name']);

        $this->assertEquals($items[0]['user_can_write'], false);
        $this->assertEquals($items[1]['user_can_write'], false);
        $this->assertEquals($items[2]['user_can_write'], false);
        $this->assertEquals($items[3]['user_can_write'], false);
        $this->assertEquals($items[4]['user_can_write'], false);
        $this->assertEquals($items[5]['user_can_write'], false);


        $this->assertEquals($items[0]['file_properties'], null);
        $this->assertEquals($items[1]['file_properties'], null);
        $this->assertEquals($items[2]['file_properties']['file_type'], 'application/pdf');
        $this->assertEquals(
            $items[2]['file_properties']['html_url'],
            '/plugins/docman/?group_id=' . urlencode($this->project_id) . '&action=show&id=' . urlencode($items[2]['id']). '&switcholdui=true'
        );
        $this->assertEquals($items[3]['file_properties'], null);
        $this->assertEquals($items[4]['file_properties'], null);
        $this->assertEquals($items[5]['file_properties'], null);

        $this->assertEquals($items[0]['link_properties'], null);
        $this->assertEquals($items[1]['link_properties'], null);
        $this->assertEquals($items[2]['link_properties'], null);
        $this->assertEquals($items[3]['link_properties']['link_url'], 'https://my.example.test');
        $this->assertEquals(
            $items[3]['link_properties']['html_url'],
            '/plugins/docman/?group_id=' . urlencode($this->project_id) . '&action=show&id=' . urlencode($items[3]['id']). '&switcholdui=true'
        );
        $this->assertEquals($items[4]['link_properties'], null);
        $this->assertEquals($items[5]['link_properties'], null);

        $this->assertEquals($items[0]['embedded_file_properties'], null);
        $this->assertEquals($items[1]['embedded_file_properties'], null);
        $this->assertEquals($items[2]['embedded_file_properties'], null);
        $this->assertEquals($items[3]['embedded_file_properties'], null);
        $this->assertEquals($items[4]['embedded_file_properties']['file_type'], 'text/html');
        $this->assertEquals(
            $items[4]['embedded_file_properties']['content'],
            file_get_contents(dirname(__DIR__) . '/_fixtures/docmanFile/embeddedFile')
        );
        $this->assertEquals($items[5]['embedded_file_properties'], null);


        $this->assertEquals($items[0]['link_properties'], null);
        $this->assertEquals($items[1]['link_properties'], null);
        $this->assertEquals($items[2]['link_properties'], null);
        $this->assertEquals($items[3]['link_properties']['link_url'], 'https://my.example.test');
        $this->assertEquals($items[4]['link_properties'], null);
        $this->assertEquals($items[5]['link_properties'], null);

        $this->assertEquals($items[0]['wiki_properties'], null);
        $this->assertEquals($items[1]['wiki_properties'], null);
        $this->assertEquals($items[2]['wiki_properties'], null);
        $this->assertEquals($items[3]['wiki_properties'], null);
        $this->assertEquals($items[4]['wiki_properties'], null);
        $this->assertEquals($items[5]['wiki_properties']['page_name'], 'MyWikiPage');
        $this->assertEquals(
            $items[5]['wiki_properties']['html_url'],
            '/plugins/docman/?group_id=' . urlencode($this->project_id) . '&action=show&id=' .
            urlencode($items[5]['id']) . '&switcholdui=true'
        );

        return $items;
    }

    /**
     * @depends testGetRootId
     */
    public function testOPTIONSDocmanItemsId($root_id)
    {
        $response = $this->getResponse(
            $this->client->options('docman_items/' . $root_id . '/docman_items'),
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME
        );

        $this->assertEquals(['OPTIONS', 'GET', 'POST'], $response->getHeader('Allow')->normalize()->toArray());
    }

    /**
     * @depends testGetRootId
     */
    public function testOPTIONSId($root_id)
    {
        $response = $this->getResponse(
            $this->client->options('docman_items/' . $root_id),
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME
        );

        $this->assertEquals(['OPTIONS', 'GET'], $response->getHeader('Allow')->normalize()->toArray());
    }

    /**
     * @depends testGetRootId
     */
    public function testGetId($root_id)
    {
        $response = $this->getResponse(
            $this->client->get('docman_items/' . $root_id),
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME
        );
        $item = $response->json();

        $this->assertEquals('Project Documentation', $item['title']);
        $this->assertEquals($root_id, $item['id']);
        $this->assertEquals('folder', $item['type']);
    }

    /**
     * @depends testGetDocumentItemsForRegularUser
     */
    public function testGetAllItemParents(array $items)
    {
        $folder_2 = $this->findItemByTitle($items, 'folder 2');

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->get('docman_items/' . $folder_2['id'] . '/docman_items')
        );
        $item = $response->json();

        $project_response = $this->getResponse($this->client->get('docman_items/' . $item[0]['id'] . '/parents'));
        $json_parents = $project_response->json();
        $this->assertEquals(count($json_parents), 3);
        $this->assertEquals($json_parents[0]['title'], 'Project Documentation');
        $this->assertEquals($json_parents[1]['title'], 'folder 1');
        $this->assertEquals($json_parents[2]['title'], 'folder 2');
    }

    /**
     * @depends testGetRootId
     */
    public function testPostEmptyDocument($root_id)
    {
        $headers = ['Content-Type' => 'application/json'];
        $query = json_encode([
            'title'       => 'Custom title',
            'description' => 'A description',
            'parent_id'   => $root_id,
            'type'        => 'empty'
        ]);

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_items', $headers, $query)
        );

        $this->assertEquals(201, $response->getStatusCode());
    }

    /**
     * @depends             testGetRootId
     * @expectedException \Guzzle\Http\Exception\ClientErrorResponseException
     * @expectExceptionCode 400
     */
    public function testPostDocumentIsRejectedIfDocumentAlreadyExists($root_id)
    {
        $headers = ['Content-Type' => 'application/json'];
        $query   = json_encode(
            [
                'title'       => 'Custom title',
                'description' => 'A description',
                'parent_id'   => $root_id,
                'type'        => 'empty'
            ]
        );

        $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_items', $headers, $query)
        );
    }

    /**
     * @depends testGetRootId
     */
    public function testPostFileDocument(int $root_id): void
    {
        $file_size = 123;
        $query     = json_encode([
            'title'           => 'File1',
            'parent_id'       => $root_id,
            'type'            => 'file',
            'file_properties' => ['file_name' => 'file1', 'file_size' => $file_size]
        ]);

        $response1 = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_items', null, $query)
        );
        $this->assertEquals(201, $response1->getStatusCode());
        $this->assertNotEmpty($response1->json()['file_properties']['upload_href']);

        $response2 = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_items', null, $query)
        );
        $this->assertEquals(201, $response1->getStatusCode());
        $this->assertSame(
            $response1->json()['file_properties']['upload_href'],
            $response2->json()['file_properties']['upload_href']
        );

        $tus_client = new Client(
            str_replace('/api/v1', '', $this->client->getBaseUrl()),
            $this->client->getConfig()
        );
        $tus_client->setSslVerification(false, false, false);
        $tus_response_upload = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $tus_client->patch(
                $response1->json()['file_properties']['upload_href'],
                [
                    'Tus-Resumable' => '1.0.0',
                    'Content-Type'  => 'application/offset+octet-stream',
                    'Upload-Offset' => '0'
                ],
                str_repeat('A', $file_size)
            )
        );
        $this->assertEquals(204, $tus_response_upload->getStatusCode());
        $this->assertEquals([$file_size], $tus_response_upload->getHeader('Upload-Offset')->toArray());

        $file_item_response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->get($response1->json()['uri'])
        );
        $this->assertEquals(200, $file_item_response->getStatusCode());
        $this->assertEquals('file', $file_item_response->json()['type']);
    }

    /**
     * @depends testGetRootId
     */
    public function testPostEmptyFileDocument(int $root_id): void
    {
        $query     = json_encode([
            'title'           => 'File2',
            'parent_id'       => $root_id,
            'type'            => 'file',
            'file_properties' => ['file_name' => 'file1', 'file_size' => 0]
        ]);

        $response1 = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_items', null, $query)
        );
        $this->assertEquals(201, $response1->getStatusCode());
        $this->assertEmpty($response1->json()['file_properties']['upload_href']);

        $file_item_response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->get($response1->json()['uri'])
        );
        $this->assertEquals(200, $file_item_response->getStatusCode());
        $this->assertEquals('file', $file_item_response->json()['type']);
    }

    /**
     * @depends testGetRootId
     * @expectedException \Guzzle\Http\Exception\ClientErrorResponseException
     * @expectExceptionCode 400
     */
    public function testPostFileDocumentIsRejectedIfFileIsTooBig(int $root_id): void
    {
        $headers = ['Content-Type' => 'application/json'];
        $query   = json_encode([
            'title'           => 'File1',
            'parent_id'       => $root_id,
            'type'            => 'file',
            'file_properties' => ['file_name' => 'file1', 'file_size' => 999999999999]
        ]);

        $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_items', $headers, $query)
        );
    }

    /**
     * @depends testGetRootId
     * @expectedException \Guzzle\Http\Exception\ClientErrorResponseException
     * @expectExceptionCode 409
     */
    public function testDocumentCreationIsRejectedIfAFileIsBeingUploadedForTheSameNameByADifferentUser(int $root_id) : void
    {
        $document_name = 'document_conflict_' . bin2hex(random_bytes(8));

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post(
                'docman_items',
                null,
                json_encode([
                    'title'           => $document_name,
                    'parent_id'       => $root_id,
                    'type'            => 'file',
                    'file_properties' => ['file_name' => 'file', 'file_size' => 123]
                ])
            )
        );
        $this->assertEquals(201, $response->getStatusCode());

        $this->getResponse(
            $this->client->post(
                'docman_items',
                null,
                json_encode([
                    'title'     => $document_name,
                    'parent_id' => $root_id,
                    'type'      => 'empty'
                ])
            )
        );
    }

    /**
     * @depends testGetRootId
     */
    public function testDocumentCreationWithASameNameIsNotRejectedWhenTheUploadHasBeenCanceled(int $root_id) : void
    {
        $document_name = 'document_not_conflict_after_cancel_' . bin2hex(random_bytes(8));

        $response_creation_file = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post(
                'docman_items',
                null,
                json_encode([
                    'title'           => $document_name,
                    'parent_id'       => $root_id,
                    'type'            => 'file',
                    'file_properties' => ['file_name' => 'file', 'file_size' => 123]
                ])
            )
        );
        $this->assertEquals(201, $response_creation_file->getStatusCode());

        $tus_client = new Client(
            str_replace('/api/v1', '', $this->client->getBaseUrl()),
            $this->client->getConfig()
        );
        $tus_client->setSslVerification(false, false, false);
        $tus_response_cancel = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $tus_client->delete(
                $response_creation_file->json()['file_properties']['upload_href'],
                ['Tus-Resumable' => '1.0.0']
            )
        );
        $this->assertEquals(204, $tus_response_cancel->getStatusCode());

        $response_creation_empty = $this->getResponse(
            $this->client->post(
                'docman_items',
                null,
                json_encode([
                    'title'     => $document_name,
                    'parent_id' => $root_id,
                    'type'      => 'empty'
                ])
            )
        );
        $this->assertEquals(201, $response_creation_empty->getStatusCode());
    }

    /**
     * @depends testGetRootId
     */
    public function testPostWikiDocument(int $root_id): void
    {
        $headers = ['Content-Type' => 'application/json'];
        $wiki_properties = ['page_name' => 'Ten steps to become a Tuleap'];
        $query = json_encode(
            [
                'title'           => 'How to become a Tuleap',
                'description'     => 'A description',
                'parent_id'       => $root_id,
                'type'            => 'wiki',
                'wiki_properties' => $wiki_properties
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_items', $headers, $query)
        );

        $this->assertEquals(201, $response->getStatusCode());
    }

    /**
     * @depends testGetRootId
     * @expectedException \Guzzle\Http\Exception\ClientErrorResponseException
     * @expectExceptionCode 400
     */
    public function testPostWikiReturns400IfTypeAndPropertiesDoesNotMatch(int $root_id): void
    {
        $headers = ['Content-Type' => 'application/json'];
        $wiki_properties = ['page_name' => 'Ten steps to become a Tuleap'];
        $query = json_encode(
            [
                'title'           => 'How to fail item creation',
                'description'     => 'A description',
                'parent_id'       => $root_id,
                'type'            => 'empty',
                'wiki_properties' => $wiki_properties
            ]
        );

        $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_items', $headers, $query)
        );
    }

    /**
     * @depends testGetRootId
     */
    public function testPostLinkDocument(int $root_id): void
    {
        $headers         = ['Content-Type' => 'application/json'];
        $link_properties = ['link_url' => 'https://turfu.example.test'];
        $query           = json_encode(
            [
                'title'           => 'To the future',
                'description'     => 'A description',
                'parent_id'       => $root_id,
                'type'            => 'link',
                'link_properties' => $link_properties
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_items', $headers, $query)
        );

        $this->assertEquals(201, $response->getStatusCode());
    }

    /**
     * @depends             testGetRootId
     * @expectedException \Guzzle\Http\Exception\ClientErrorResponseException
     * @expectExceptionCode 400
     */
    public function testPostLinkReturns400IfTypeAndPropertiesDoesNotMatch(int $root_id): void
    {
        $headers         = ['Content-Type' => 'application/json'];
        $wiki_properties = ['page_name' => 'Ten steps to become a Tuleap'];
        $query           = json_encode(
            [
                'title'           => 'To the future',
                'description'     => 'A description',
                'parent_id'       => $root_id,
                'type'            => 'link',
                'wiki_properties' => $wiki_properties
            ]
        );

        $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_items', $headers, $query)
        );
    }
    /**
     * @depends testGetDocumentItemsForRegularUser
     * @expectedException \Guzzle\Http\Exception\ClientErrorResponseException
     * @expectExceptionCode 403
     */
    public function testPostReturns403WhenPermissionDenied(array $stored_items)
    {
        $folder_3 = $this->findItemByTitle($stored_items, 'folder 3');

        $query = json_encode([
            'title' => 'A title',
            'description' => 'A description',
            'parent_id' => $folder_3['id'],
            'type' => 'empty'
        ]);

        $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_items', null, $query)
        );
    }

    /**
     * @depends testGetRootId
     */
    public function testPostFolderItem(int $root_id): void
    {
        $headers = ['Content-Type' => 'application/json'];
        $query   = json_encode(
            [
                'title'       => 'My Folder',
                'description' => 'A Folder description',
                'parent_id'   => $root_id,
                'type'        => 'folder'
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_items', $headers, $query)
        );

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertNull($response->json()['file_properties']);
    }

    /**
     * @depends             testGetRootId
     * @expectedException \Guzzle\Http\Exception\ClientErrorResponseException
     * @expectExceptionCode 400
     */
    public function testPostFolderReturns400IfTypeAndPropertiesDoesNotMatch(int $root_id): void
    {
        $headers         = ['Content-Type' => 'application/json'];
        $link_properties = ['link_url' => 'https://turfu.example.test'];
        $query           = json_encode(
            [
                'title'           => 'To the fail future',
                'parent_id'       => $root_id,
                'type'            => 'folder',
                'link_properties' => $link_properties
            ]
        );

        $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_items', $headers, $query)
        );
    }

    /**
     * Find first item in given array of items which has given title.
     * @return array|null Found item. null otherwise.
     */
    private function findItemByTitle(array $items, $title)
    {
        $index = array_search($title, array_column($items, 'title'));
        if ($index === false) {
            return null;
        }
        return $items[$index];
    }

    /**
     * @depends testGetRootId
     */
    public function testPostEmbeddedDocument(int $root_id): void
    {
        $headers = ['Content-Type' => 'application/json'];
        $embedded_properties = ['content' => 'step1 : Avoid to sort items in the docman'];
        $query = json_encode(
            [
                'title'           => 'How to become a Tuleap (embedded version)',
                'description'     => 'A description',
                'parent_id'       => $root_id,
                'type'            => 'embedded',
                'embedded_properties' => $embedded_properties
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_items', $headers, $query)
        );

        $this->assertEquals(201, $response->getStatusCode());
    }

    /**
     * @depends             testGetRootId
     * @expectedException \Guzzle\Http\Exception\ClientErrorResponseException
     * @expectExceptionCode 400
     */
    public function testPostEmbeddedReturns400IfTypeAndPropertiesDoesNotMatch(int $root_id): void
    {
        $headers         = ['Content-Type' => 'application/json'];
        $wiki_properties = ['page_name' => 'Ten steps to become a Tuleap'];
        $query           = json_encode(
            [
                'title'           => 'To the future',
                'description'     => 'A description',
                'parent_id'       => $root_id,
                'type'            => 'link',
                'embedded_properties' => $wiki_properties
            ]
        );

        $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_items', $headers, $query)
        );
    }
}
