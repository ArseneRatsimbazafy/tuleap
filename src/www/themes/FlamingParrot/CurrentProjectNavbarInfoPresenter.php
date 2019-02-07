<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

class FlamingParrot_CurrentProjectNavbarInfoPresenter  // phpcs:ignore
{
    public $project_privacy;
    public $project_link;
    public $project_is_public;
    public $project_name;
    /**
     * @var string[]
     */
    public $project_flags;
    /**
     * @var bool
     */
    public $has_project_flags;

    public function __construct(Project $project, $project_privacy, array $project_flags)
    {
        $purifier = Codendi_HTMLPurifier::instance();

        $this->project_link      = '/projects/' . $project->getUnixName() . '/';
        $this->project_is_public = $project->isPublic();
        $this->project_name      = $project->getUnconvertedPublicName();
        $this->project_privacy   = $purifier->purify(
            $GLOBALS['Language']->getText('project_privacy', 'tooltip_' . $project_privacy),
            CODENDI_PURIFIER_STRIP_HTML
        );
        $this->project_flags     = $project_flags;
        $this->has_project_flags = count($project_flags) > 0;
    }
}
