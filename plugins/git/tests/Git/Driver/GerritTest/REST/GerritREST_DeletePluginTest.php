<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

require_once 'GerritREST_Base.php';

class Git_DriverREST_Gerrit_DeletePluginTest extends Git_Driver_GerritREST_base implements Git_Driver_Gerrit_DeletePluginTest
{

    private $response_with_plugin;
    private $response_without_plugin;

    public function setUp()
    {
        parent::setUp();
        $this->response_with_plugin = <<<EOS
)]}'
{
  "deleteproject": {
    "kind": "gerritcodereview#plugin",
    "id": "deleteproject",
    "version": "v2.8.2"
  },
  "replication": {
    "kind": "gerritcodereview#plugin",
    "id": "replication",
    "version": "v2.8.1"
  }
}
EOS;
        $this->response_without_plugin = <<<EOS
)]}'
{
  "replication": {
    "kind": "gerritcodereview#plugin",
    "id": "replication",
    "version": "v2.8.1"
  }
}
EOS;
    }
    public function itReturnsFalseIfPluginIsNotInstalled()
    {
        $response = stub('Guzzle\Http\Message\Response')->getBody(true)->returns($this->response_without_plugin);
        stub($this->guzzle_request)->send()->returns($response);
        stub($this->guzzle_client)->get()->returns($this->guzzle_request);

        $enabled = $this->driver->isDeletePluginEnabled($this->gerrit_server);

        $this->assertFalse($enabled);
    }

    public function itReturnsFalseIfPluginIsInstalledAndNotEnabled()
    {
        $response = stub('Guzzle\Http\Message\Response')->getBody(true)->returns($this->response_without_plugin);
        stub($this->guzzle_request)->send()->returns($response);
        stub($this->guzzle_client)->get()->returns($this->guzzle_request);

        $enabled = $this->driver->isDeletePluginEnabled($this->gerrit_server);

        $this->assertFalse($enabled);
    }

    public function itReturnsTrueIfPluginIsInstalledAndEnabled()
    {
        $response = stub('Guzzle\Http\Message\Response')->getBody(true)->returns($this->response_with_plugin);
        stub($this->guzzle_request)->send()->returns($response);
        stub($this->guzzle_client)->get()->returns($this->guzzle_request);

        $enabled = $this->driver->isDeletePluginEnabled($this->gerrit_server);

        $this->assertTrue($enabled);
    }

    public function itCallsGerritServerWithOptions()
    {
        $url = $this->gerrit_server_host
            .':'. $this->gerrit_server_port
            .'/a/plugins/';

        $response = stub('Guzzle\Http\Message\Response')->getBody(true)->returns('');
        stub($this->guzzle_request)->send()->returns($response);

        expect($this->guzzle_client)->get(
            $url,
            array(
                'verify' => false,
            )
        )->once();
        stub($this->guzzle_client)->get()->returns($this->guzzle_request);

        $this->driver->isDeletePluginEnabled($this->gerrit_server);
    }

    public function itThrowsAProjectDeletionExceptionIfThereAreOpenChanges()
    {
        $exception = new Guzzle\Http\Exception\ClientErrorResponseException();
        stub($this->guzzle_client)->delete()->throws($exception);

        $this->expectException('ProjectDeletionException');

        $this->driver->deleteProject($this->gerrit_server, 'project');
    }
}
