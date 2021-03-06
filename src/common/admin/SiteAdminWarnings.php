<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\Admin;

use Event;
use EventManager;
use ForgeConfig;
use ForgeUpgradeConfig;
use PermissionsOverrider_PermissionsOverriderManager;
use Tuleap\Admin\Homepage\NbUsersByStatus;

final class SiteAdminWarnings
{
    /**
     * @var EventManager
     */
    private $event_manager;
    /**
     * @var ForgeUpgradeConfig
     */
    private $forge_upgrade_config;

    public function __construct(EventManager $event_manager, ForgeUpgradeConfig $forge_upgrade_config)
    {
        $this->event_manager        = $event_manager;
        $this->forge_upgrade_config = $forge_upgrade_config;
    }

    public function getAdminHomeWarningsWithUsersByStatus(NbUsersByStatus $nb_users_by_status): string
    {
        $warnings = [];
        $this->event_manager->processEvent(
            Event::GET_SITEADMIN_WARNINGS,
            [
                'nb_users_by_status' => $nb_users_by_status,
                'warnings'           => &$warnings
            ]
        );

        if (! ForgeConfig::get('disable_forge_upgrade_warnings')) {
            $this->forge_upgrade_config->loadDefaults();
            if (! $this->forge_upgrade_config->isSystemUpToDate()) {
                $warnings[] = '<div class="tlp-alert-warning alert alert-warning alert-block">' . $GLOBALS['Language']->getText('admin_main', 'forgeupgrade') . '</div>';
            }
        }

        if (PermissionsOverrider_PermissionsOverriderManager::instance()->hasOverrider()) {
            $warnings[] = '<div class="tlp-alert-danger alert-block">' . _('There is a PermissionOverrider in `/etc/tuleap/local_glue/PermissionsOverrider.php` it\'s deprecated without replacement. You should remove this file now. The feature will be removed end of March 2021.') . '</div>';
        }

        return implode('', $warnings);
    }
}
