<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\User\AccessKey;

class AccessKeyCreator
{
    /**
     * @var LastAccessKeyIdentifierStore
     */
    private $last_access_key_identifier_store;
    /**
     * @var AccessKeyDAO
     */
    private $dao;
    /**
     * @var AccessKeyVerificationStringHasher
     */
    private $hasher;
    /**
     * @var AccessKeyCreationNotifier
     */
    private $notifier;

    public function __construct(
        LastAccessKeyIdentifierStore $last_access_key_identifier_store,
        AccessKeyDAO $dao,
        AccessKeyVerificationStringHasher $hasher,
        AccessKeyCreationNotifier $notifier
    ) {
        $this->last_access_key_identifier_store = $last_access_key_identifier_store;
        $this->dao                              = $dao;
        $this->hasher                           = $hasher;
        $this->notifier                         = $notifier;
    }

    public function create(\PFUser $user, $description)
    {
        $verification_string = AccessKeyVerificationString::generateNewAccessKeyVerificationString();
        $current_time        = new \DateTimeImmutable();

        $key_id     = $this->dao->create(
            $user->getId(),
            $this->hasher->computeHash($verification_string),
            $current_time->getTimestamp(),
            $description
        );
        $access_key = new AccessKey($key_id, $verification_string);

        $this->notifier->notifyCreation($user, $description);
        $this->last_access_key_identifier_store->storeLastGeneratedAccessKeyIdentifier($access_key);
    }
}
