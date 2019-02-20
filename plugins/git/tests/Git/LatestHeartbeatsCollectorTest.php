<?php
/**
 * Copyright (c) Enalean, 2017-2019. All Rights Reserved.
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

namespace Tuleap\Git;

require_once __DIR__ .'/../bootstrap.php';

use Tuleap\Project\HeartbeatsEntryCollection;
use TuleapTestCase;

class LatestHeartbeatsCollectorTest extends TuleapTestCase
{
    /** @var \Tracker_ArtifactFactory */
    public $factory;

    /** @var LatestHeartbeatsCollector */
    public $collector;

    /** @var \Project */
    public $project;

    /** @var \PFUser */
    public $user;

    public function setUp()
    {
        parent::setUp();

        $glyph_finder = mock('Tuleap\\Glyph\\GlyphFinder');
        stub($glyph_finder)->get()->returns(mock('Tuleap\\Glyph\\Glyph'));

        $this->project = aMockProject()->withId(101)->build();
        $this->user    = aUser()->withId(200)->build();

        $dao = \Mockery::mock(\Git_LogDao::class);
        $dao->shouldReceive('searchLatestPushesInProject')
            ->with(101, HeartbeatsEntryCollection::NB_MAX_ENTRIES)
            ->andReturn([
                ['repository_id' => 1, 'user_id' => 101, 'push_date' => 1234, 'commits_number' => 1],
                ['repository_id' => 2, 'user_id' => 101, 'push_date' => 1234, 'commits_number' => 1],
                ['repository_id' => 3, 'user_id' => 101, 'push_date' => 1234, 'commits_number' => 1]
            ]);

        $this->factory = mock('GitRepositoryFactory');
        $this->declareRepository(1, true);
        $this->declareRepository(2, false);
        $this->declareRepository(3, true);

        $this->collector = new LatestHeartbeatsCollector(
            $this->factory,
            $dao,
            mock('Git_GitRepositoryUrlManager'),
            $glyph_finder,
            mock('UserManager'),
            mock('UserHelper')
        );
    }

    private function declareRepository($id, $user_can_read)
    {
        $repository = stub('GitRepository')->getId()->returns($id);

        stub($repository)->userCanRead()->returns($user_can_read);
        stub($repository)->getProject()->returns($this->project);

        stub($this->factory)->getRepositoryById($id)->returns($repository);
    }

    public function itCollectsOnlyPushesForRepositoriesUserCanView()
    {
        expect($this->factory)->getRepositoryById()->count(3);

        $collection = new HeartbeatsEntryCollection($this->project, $this->user);
        $this->collector->collect($collection);

        $this->assertCount($collection->getLatestEntries(), 2);
    }

    public function itInformsThatThereIsAtLeastOneActivityThatUserCannotRead()
    {
        $collection = new HeartbeatsEntryCollection($this->project, $this->user);
        $this->collector->collect($collection);

        $this->assertTrue($collection->areThereActivitiesUserCannotSee());
    }
}
