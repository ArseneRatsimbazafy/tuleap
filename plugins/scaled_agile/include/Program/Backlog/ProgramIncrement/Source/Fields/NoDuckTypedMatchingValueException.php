<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Source\Fields;

final class NoDuckTypedMatchingValueException extends \RuntimeException implements FieldSynchronizationException
{
    public function __construct(string $source_value_label, int $team_status_field_id, int $tracker_id)
    {
        parent::__construct(
            sprintf(
                'No matching value found for value %s in field #%d of tracker #%d by duck typing.',
                $source_value_label,
                $team_status_field_id,
                $tracker_id
            )
        );
    }
}
