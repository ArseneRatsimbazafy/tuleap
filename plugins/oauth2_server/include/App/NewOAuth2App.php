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

namespace Tuleap\OAuth2Server\App;

use Tuleap\Authentication\SplitToken\SplitTokenVerificationString;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;

/**
 * @psalm-immutable
 */
final class NewOAuth2App
{
    /**
     * @var string
     */
    private $name;
    /**
     * @var string
     */
    private $redirect_endpoint;
    /**
     * @var SplitTokenVerificationString
     */
    private $secret;
    /**
     * @var string
     */
    private $hashed_secret;
    /**
     * @var \Project
     */
    private $project;

    private function __construct(
        string $name,
        string $redirect_endpoint,
        SplitTokenVerificationString $secret,
        string $hashed_secret,
        \Project $project
    ) {
        $this->name              = $name;
        $this->redirect_endpoint = $redirect_endpoint;
        $this->secret            = $secret;
        $this->hashed_secret     = $hashed_secret;
        $this->project           = $project;
    }

    /**
     * @throws InvalidAppDataException
     */
    public static function fromAppData(
        string $name,
        string $redirect_endpoint,
        \Project $project,
        SplitTokenVerificationStringHasher $hasher
    ): self {
        $is_data_valid = self::isAppDataValid($name, $redirect_endpoint);

        if (! $is_data_valid) {
            throw new InvalidAppDataException();
        }

        $secret = SplitTokenVerificationString::generateNewSplitTokenVerificationString();

        return new self(
            $name,
            $redirect_endpoint,
            $secret,
            $hasher->computeHash($secret),
            $project
        );
    }

    private static function isAppDataValid(string $name, string $redirect_endpoint): bool
    {
        $string_validator = new \Valid_String();
        $string_validator->required();
        // See https://tools.ietf.org/html/rfc6749#section-3.1.2
        $redirect_endpoint_validator = new \Valid_String();
        $redirect_endpoint_validator->required();
        $redirect_endpoint_validator->addRule(new \Rule_Regexp('/^https:\/\/[^#]*$/i'));

        return $string_validator->validate($name) && $redirect_endpoint_validator->validate($redirect_endpoint);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getRedirectEndpoint(): string
    {
        return $this->redirect_endpoint;
    }

    public function getProject(): \Project
    {
        return $this->project;
    }

    public function getSecret(): SplitTokenVerificationString
    {
        return $this->secret;
    }

    public function getHashedSecret(): string
    {
        return $this->hashed_secret;
    }
}
