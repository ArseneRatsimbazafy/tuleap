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

namespace Tuleap\date\Admin;

use Feedback;
use HTTPRequest;
use Tuleap\date\DefaultRelativeDatesDisplayPreferenceRetriever;
use Tuleap\date\SelectedDateDisplayPreferenceValidator;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;

class RelativeDatesDisplaySaveController implements DispatchableWithRequest
{
    /**
     * @var \CSRFSynchronizerToken
     */
    private $csrf_token;

    /**
     * @var SelectedDateDisplayPreferenceValidator
     */
    private $date_display_preference_validator;

    /**
     * @var \ConfigDao
     */
    private $config_dao;

    public function __construct(
        \CSRFSynchronizerToken $csrf_token,
        SelectedDateDisplayPreferenceValidator $date_display_preference_validator,
        \ConfigDao $config_dao
    ) {
        $this->csrf_token                        = $csrf_token;
        $this->date_display_preference_validator = $date_display_preference_validator;
        $this->config_dao                        = $config_dao;
    }

    /**
     * @throws ForbiddenException
     */
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables): void
    {
        $this->csrf_token->check();

        if (! $request->getCurrentUser()->isSuperUser()) {
            throw new ForbiddenException();
        }

        $new_relative_dates_display   = (string) $request->get('relative-dates-display');
        $is_provided_preference_valid = $this->date_display_preference_validator->validateSelectedUserPreference(
            $new_relative_dates_display
        );

        if (! $is_provided_preference_valid) {
            $layout->addFeedback(Feedback::ERROR, _('Submitted relative dates display is not valid.'));
            $this->redirect($layout);
        }

        $this->config_dao->save(DefaultRelativeDatesDisplayPreferenceRetriever::DEFAULT_RELATIVE_DATES_DISPLAY, $new_relative_dates_display);
        $layout->addFeedback(Feedback::INFO, _("Default relative dates display preference saved successfully."));
        $this->redirect($layout);
    }

    private function redirect(BaseLayout $layout): void
    {
        $layout->redirect(RelativeDatesDisplayController::URL);
    }
}