<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright (c) Enalean, 2015-2019. All Rights Reserved.
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

namespace Tuleap;

use Delight\Cookie\Cookie;
use ForgeConfig;

/**
 * Tuleap\CookieManager
 *
 * Manages cookies
 */
class CookieManager
{
    public function setCookie($name, $value, $expire = 0)
    {
        $cookie = $this->buildCookie($name);
        $cookie->setValue($value);
        $cookie->setExpiryTime($expire);

        $this->sendHTTPHeader($cookie);
    }

    /**
     * @return Cookie
     */
    private function buildCookie($name)
    {
        $cookie = new Cookie(self::getCookieName($name));
        $cookie->setHttpOnly(true);
        $cookie->setSecureOnly(self::canCookieUseSecureFlag());
        $cookie->setSameSiteRestriction(Cookie::SAME_SITE_RESTRICTION_LAX);

        return $cookie;
    }

    /**
     * @return bool
     */
    public static function canCookieUseSecureFlag()
    {
        return (bool)ForgeConfig::get('sys_https_host');
    }

    public function getCookie($name)
    {
        return Cookie::get(self::getCookieName($name), '');
    }

    /**
     * @return bool
     */
    public function isCookie($name)
    {
        return Cookie::exists(self::getCookieName($name));
    }

    public function removeCookie($name)
    {
        $cookie = $this->buildCookie($name);
        $cookie->setValue('');
        $this->sendHTTPHeader($cookie);
    }

    /**
     * @return string
     */
    public static function getCookieName($name)
    {
        $cookie_prefix = ForgeConfig::get('sys_cookie_prefix');
        $cookie_name   = "${cookie_prefix}_${name}";

        if (!self::canCookieUseSecureFlag()) {
            return $cookie_name;
        }

        return Cookie::PREFIX_HOST . $cookie_name;
    }

    private function sendHTTPHeader(Cookie $cookie) : void
    {
        $header = (string) $cookie;
        if ($header === '' || headers_sent()) {
            return;
        }
        header($header, false);
    }
}
