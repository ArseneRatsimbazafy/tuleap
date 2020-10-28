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

namespace Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Source\Fields;

class FieldStatusAdapter
{
    /**
     * @var \Tracker_Semantic_StatusFactory
     */
    private $status_factory;

    public function __construct(
        \Tracker_Semantic_StatusFactory $status_factory
    ) {
        $this->status_factory = $status_factory;
    }

    /**
     * @throws FieldRetrievalException
     */
    public function build(\Tracker $source_tracker): FieldData
    {
        $status_field = $this->status_factory->getByTracker($source_tracker)->getField();
        if (! $status_field) {
            throw new FieldRetrievalException($source_tracker->getId(), "Status");
        }

        return new FieldData($status_field);
    }
}