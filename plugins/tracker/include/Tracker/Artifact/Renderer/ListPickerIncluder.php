<?php
/*
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

namespace Tuleap\Tracker\Artifact\Renderer;

use HTTPRequest;
use Tuleap\BrowserDetection\DetectedBrowser;

final class ListPickerIncluder
{
    /**
     * Feature flag to have list pickers in lieu of <select> in artifact views
     *
     * @tlp-config-key
     */
    public const FORGE_CONFIG_KEY = 'feature_flag_use_list_pickers_in_trackers_and_modals';

    public static function includeListPickerAssets(HTTPRequest $request, int $tracker_id): void
    {
        if (self::isListPickerEnabledAndBrowserCompatible($request, $tracker_id)) {
            $include_assets = new \Tuleap\Layout\IncludeAssets(
                __DIR__ . '/../../../../../../src/www/assets/trackers',
                '/assets/trackers'
            );

            $GLOBALS['HTML']->includeFooterJavascriptFile($include_assets->getFileURL('list-fields.js'));
        }
    }

    public static function isListPickerEnabledAndBrowserCompatible(HTTPRequest $request, int $tracker_id): bool
    {
        if (self::isListPickerEnabledOnPlatform() === false) {
            return false;
        }

        if (self::isFeatureDisabledForCurrentTracker($tracker_id)) {
            return false;
        }

        $detected_browser = DetectedBrowser::detectFromTuleapHTTPRequest($request);
        return ! $detected_browser->isIE() && ! $detected_browser->isEdgeLegacy();
    }

    public static function isListPickerEnabledOnPlatform(): bool
    {
        return \ForgeConfig::get(self::FORGE_CONFIG_KEY) !== "0";
    }

    /**
     * @return string[]
     */
    public static function getTrackersHavingListPickerDisabled(): array
    {
        $config_value               = \ForgeConfig::get(self::FORGE_CONFIG_KEY);
        $tracker_id_prefix_position = strpos($config_value, "t:");
        if ($tracker_id_prefix_position === false) {
            return [];
        }

        return explode(
            ",",
            str_replace(
                "t:",
                "",
                $config_value
            )
        );
    }

    private static function isFeatureDisabledForCurrentTracker(int $tracker_id): bool
    {
        return array_search($tracker_id, self::getTrackersHavingListPickerDisabled()) !== false;
    }
}
