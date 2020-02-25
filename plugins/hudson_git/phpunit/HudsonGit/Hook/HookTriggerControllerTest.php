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

namespace Tuleap\HudsonGit\Hook;

use DateTimeImmutable;
use GitRepository;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Project;
use Psr\Log\LoggerInterface;
use Tuleap\HudsonGit\Git\Administration\JenkinsServer;
use Tuleap\HudsonGit\Git\Administration\JenkinsServerFactory;
use Tuleap\HudsonGit\Job\JobManager;
use Tuleap\HudsonGit\PollingResponse;

class HookTriggerControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var HookTriggerController
     */
    private $controller;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|HookDao
     */
    private $dao;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|JenkinsClient
     */
    private $jenkins_client;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|LoggerInterface
     */
    private $logger;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|JobManager
     */
    private $job_manager;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|JenkinsServerFactory
     */
    private $jenkins_server_factory;

    /**
     * @var GitRepository|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $repository;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Project
     */
    private $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dao                    = Mockery::mock(HookDao::class);
        $this->jenkins_client         = Mockery::mock(JenkinsClient::class);
        $this->logger                 = Mockery::mock(LoggerInterface::class);
        $this->job_manager            = Mockery::mock(JobManager::class);
        $this->jenkins_server_factory = Mockery::mock(JenkinsServerFactory::class);

        $this->controller = new HookTriggerController(
            $this->dao,
            $this->jenkins_client,
            $this->logger,
            $this->job_manager,
            $this->jenkins_server_factory
        );

        $this->repository = Mockery::mock(GitRepository::class);

        $this->project = Mockery::mock(Project::class);
        $this->repository->shouldReceive('getProject')->andReturn($this->project);
        $this->repository->shouldReceive('getId')->andReturn(1);

        $this->repository->shouldReceive('getAccessURL')->andReturn([
            'ssh'  => 'example.com/repo01',
            'http' => 'https://example.com/repo01'
        ]);
    }

    public function testItTriggersRepositoryHooks(): void
    {
        $this->dao->shouldReceive('searchById')->once()->with(1)->andReturn([
           ['jenkins_server_url' => 'https://example.com/jenkins']
        ]);

        $polling_response = Mockery::mock(PollingResponse::class);
        $polling_response->shouldReceive('getJobPaths')->andReturn([
            'https://example.com/jenkins/job01'
        ]);
        $polling_response->shouldReceive('getBody')->andReturn('Response body');

        $this->jenkins_client->shouldReceive('pushGitNotifications')->times(2)->andReturn($polling_response);
        $this->job_manager->shouldReceive('create')->times(2);
        $this->job_manager->shouldReceive('createJobLogForProject')->never();

        $this->logger->shouldReceive('debug');
        $this->logger->shouldReceive('error')->never();

        $this->jenkins_server_factory->shouldReceive('getJenkinsServerOfProject')->once()->andReturn([]);

        $date_time = new DateTimeImmutable();

        $this->controller->trigger(
            $this->repository,
            'a',
            $date_time
        );
    }

    public function testItTriggersProjectHooks(): void
    {
        $this->dao->shouldReceive('searchById')->once()->with(1)->andReturn([]);

        $jenkins_server = new JenkinsServer(0, 'https://example.com/jenkins', $this->project);
        $this->jenkins_server_factory->shouldReceive('getJenkinsServerOfProject')->once()->andReturn([
            $jenkins_server
        ]);

        $polling_response = Mockery::mock(PollingResponse::class);
        $polling_response->shouldReceive('getJobPaths')->andReturn([
            'https://example.com/jenkins/job01'
        ]);
        $polling_response->shouldReceive('getBody')->andReturn('Response body');

        $this->jenkins_client->shouldReceive('pushGitNotifications')->times(2)->andReturn($polling_response);
        $this->job_manager->shouldReceive('create')->never();
        $this->job_manager->shouldReceive('createJobLogForProject')->times(2);

        $this->logger->shouldReceive('debug');
        $this->logger->shouldReceive('error')->never();

        $date_time = new DateTimeImmutable();

        $this->controller->trigger(
            $this->repository,
            'a',
            $date_time
        );
    }
}
