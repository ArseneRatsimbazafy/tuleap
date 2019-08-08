/*
 * Copyright (c) Enalean, 2019-present. All Rights Reserved.
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
import uuid from "uuid/v4";

export function initStepField(state, [steps, field_id, empty_step]) {
    state.steps = steps.map(step => {
        return { ...step, uuid: uuid() };
    });
    state.field_id = field_id;
    state.empty_step = empty_step;
}

export function deleteStep(state, step) {
    const index = state.steps.indexOf(step);
    if (index > -1) {
        state.steps.splice(index, 1);
    }
}

export function addStep(state, index) {
    const step = Object.assign({}, state.empty_step);
    step.uuid = uuid();

    state.steps.splice(index, 0, step);
}
