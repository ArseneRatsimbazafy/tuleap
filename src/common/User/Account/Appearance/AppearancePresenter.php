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
 *
 */

declare(strict_types=1);

namespace Tuleap\User\Account\Appearance;

use CSRFSynchronizerToken;
use Tuleap\User\Account\AccountTabPresenterCollection;

/**
 * @psalm-immutable
 */
final class AppearancePresenter
{
    /**
     * @var CSRFSynchronizerToken
     */
    public $csrf_token;
    /**
     * @var AccountTabPresenterCollection
     */
    public $tabs;
    /**
     * @var LanguagePresenter[]
     */
    public $languages;
    /**
     * @var false|string
     */
    public $json_encoded_colors;
    /**
     * @var bool
     */
    public $is_condensed;
    /**
     * @var string
     */
    public $current_color;
    /**
     * @var bool
     */
    public $is_accessibility_enabled;
    /**
     * @var bool
     */
    public $is_realname_login;
    /**
     * @var bool
     */
    public $is_login_realname;
    /**
     * @var bool
     */
    public $is_login;
    /**
     * @var bool
     */
    public $is_realname;
    /**
     * @var int
     */
    public $username_display_value_realname_login;
    /**
     * @var int
     */
    public $username_display_value_login_realname;
    /**
     * @var int
     */
    public $username_display_value_login;
    /**
     * @var int
     */
    public $username_display_value_realname;

    /**
     * @param LanguagePresenter[]   $languages
     * @param ThemeColorPresenter[] $colors
     */
    public function __construct(
        CSRFSynchronizerToken $csrf_token,
        AccountTabPresenterCollection $tabs,
        array $languages,
        array $colors,
        bool $is_condensed,
        bool $is_accessibility_enabled,
        bool $is_realname_login,
        bool $is_login_realname,
        bool $is_login,
        bool $is_realname
    ) {
        $this->csrf_token               = $csrf_token;
        $this->tabs                     = $tabs;
        $this->languages                = $languages;
        $this->json_encoded_colors      = json_encode($colors);
        $this->is_condensed             = $is_condensed;
        $this->is_accessibility_enabled = $is_accessibility_enabled;
        $this->is_realname_login        = $is_realname_login;
        $this->is_login_realname        = $is_login_realname;
        $this->is_login                 = $is_login;
        $this->is_realname              = $is_realname;

        $this->username_display_value_realname_login = \UserHelper::PREFERENCES_NAME_AND_LOGIN;
        $this->username_display_value_login_realname = \UserHelper::PREFERENCES_LOGIN_AND_NAME;
        $this->username_display_value_login          = \UserHelper::PREFERENCES_LOGIN;
        $this->username_display_value_realname       = \UserHelper::PREFERENCES_REAL_NAME;

        $this->current_color = '';
        foreach ($colors as $color) {
            if ($color->selected) {
                $this->current_color = $color->id;
            }
        }
    }
}