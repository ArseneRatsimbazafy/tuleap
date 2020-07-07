<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\Creation\JiraImporter\Import\Artifact;

use PFUser;
use Psr\Log\LoggerInterface;

class JiraAuthorRetriever
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var \UserManager
     */
    private $user_manager;

    /**
     * @var JiraUserOnTuleapCache
     */
    private $user_cache;

    public function __construct(
        LoggerInterface $logger,
        \UserManager $user_manager,
        JiraUserOnTuleapCache $user_cache
    ) {
        $this->logger       = $logger;
        $this->user_manager = $user_manager;
        $this->user_cache   = $user_cache;
    }

    public function getArtifactSubmitter(IssueAPIRepresentation $issue, PFUser $forge_user): PFUser
    {
        $creator    = $issue->getFieldByKey('creator');
        $account_id = $creator['accountId'];

        if ($creator === null) {
            return $forge_user;
        }

        $display_name = $creator['displayName'];

        if ($this->user_cache->isUserCached($account_id)) {
            $this->logger->debug("User $display_name is already in cache, skipping...");

            return $this->user_cache->getUserFromCacheByJiraAccountId(
                $account_id
            );
        }

        if (! isset($creator['emailAddress'])) {
            $this->logger->debug("Jira user $display_name does not share his/her email address, skipping...");
            $this->user_cache->cacheUser($forge_user, $account_id);

            return $forge_user;
        }

        $email_address  = $creator['emailAddress'];
        $matching_users = $this->user_manager->getAllUsersByEmail($email_address);

        if (count($matching_users) !== 1) {
            $this->logger->debug("Unable to identify an unique user on Tuleap side for Jira user $display_name");

            $this->user_cache->cacheUser($forge_user, $account_id);
            return $forge_user;
        }

        $tuleap_user           = $matching_users[0];
        $tuleap_user_real_name = $tuleap_user->getRealName();

        $this->user_cache->cacheUser($tuleap_user, $account_id);
        $this->logger->debug("Jira user $display_name has been identified as Tuleap user $tuleap_user_real_name");

        return $tuleap_user;
    }
}