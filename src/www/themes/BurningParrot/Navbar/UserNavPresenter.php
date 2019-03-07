<?php
/**
 * Copyright (c) Enalean, 2016 - 2017. All Rights Reserved.
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

namespace Tuleap\Theme\BurningParrot\Navbar;

use HTTPRequest;
use PFUser;
use CSRFSynchronizerToken;
use Tuleap\Glyph\GlyphFinder;
use URLRedirect;
use Tuleap\Theme\BurningParrot\Navbar\DropdownMenuItem\Presenter as DropdownMenuItemPresenter;
use Tuleap\Theme\BurningParrot\Navbar\DropdownMenuItem\Content\History\UserHistoryPresenter;
use Tuleap\Theme\BurningParrot\Navbar\MenuItem\Presenter as MenuItemPresenter;
use Tuleap\Theme\BurningParrot\Navbar\MenuItem\LogoutPresenter;

class UserNavPresenter
{
    /** @var HTTPRequest */
    private $request;

    /** @var PFUser */
    private $current_user;

    /** @var boolean */
    public $display_new_user_menu_item;
    /**
     * @var URLRedirect
     */
    private $url_redirect;
    /**
     * @var GlyphFinder
     */
    private $glyph_finder;

    public function __construct(
        HTTPRequest $request,
        PFUser $current_user,
        $display_new_user_menu_item,
        URLRedirect $url_redirect,
        GlyphFinder $glyph_finder
    ) {
        $this->request                    = $request;
        $this->current_user               = $current_user;
        $this->display_new_user_menu_item = $display_new_user_menu_item;
        $this->url_redirect               = $url_redirect;
        $this->glyph_finder               = $glyph_finder;
    }

    public function is_user_logged_in()
    {
        return $this->current_user->isLoggedIn();
    }

    public function user_real_name()
    {
        return $this->current_user->getRealName();
    }

    public function user_user_name()
    {
        return $this->current_user->getUserName();
    }

    public function user_has_avatar()
    {
        return $this->current_user->hasAvatar();
    }

    public function user_avatar_url()
    {
        return $this->current_user->getAvatarUrl();
    }

    public function user_avatar_alt()
    {
        return _('User avatar');
    }

    public function my_account_label()
    {
        return $GLOBALS['Language']->getText('menu', 'my_personal_page');
    }

    public function login_menu_item()
    {
        return new MenuItemPresenter(
            $GLOBALS['Language']->getText('include_menu', 'login'),
            $this->url_redirect->buildReturnToLogin($_SERVER),
            'fa fa-sign-in',
            '',
            []
        );
    }

    public function new_user_menu_item()
    {
        return new MenuItemPresenter(
            $GLOBALS['Language']->getText('include_menu', 'new_user'),
            '/account/register.php',
            'fa fa-user-plus',
            '',
            []
        );
    }

    public function user_history_dropdown()
    {
        return new DropdownMenuItemPresenter(
            _('History'),
            'fa fa-history',
            new UserHistoryPresenter(
                'user-history',
                $this->current_user,
                $this->glyph_finder
            ),
            'only-icon without-carret nav-dropdown-right'
        );
    }

    public function user_nav_items()
    {
        return array(
            new MenuItemPresenter(
                $GLOBALS['Language']->getText('my_index', 'account_maintenance'),
                '/account/',
                'fa fa-cog',
                'only-icon',
                []
            )
        );
    }

    public function logout_menu_item()
    {
        $logout_csrf = new CSRFSynchronizerToken('logout_action');
        return new LogoutPresenter(
            $GLOBALS['Language']->getText('include_menu', 'logout'),
            $logout_csrf
        );
    }
}
