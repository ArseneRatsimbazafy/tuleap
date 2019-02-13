/*
 * Copyright Enalean (c) 2018. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

import mutations from "./mutations.js";
import initial_state from "./state.js";
import { REST_FEEDBACK_ADD, ERROR_OCCURRED, REST_FEEDBACK_EDIT } from "../../../constants.js";

describe("Store mutations", () => {
    let state;
    beforeEach(() => {
        state = { ...initial_state };
    });

    describe("setters", () => {
        it("Given a widget with state initialisation, Then we change reading mode, state must change too", () => {
            mutations.toggleReadingMode(state);
            expect(state.reading_mode).toBe(false);
        });

        it("Given a widget with state initialisation, Then we put new dates, state must change too", () => {
            mutations.toggleReadingMode(state);
            mutations.setParametersForNewQuery(state, ["2018-01-01", "2018-02-02"]);
            expect(state.start_date).toEqual("2018-01-01");
            expect(state.end_date).toEqual("2018-02-02");
            expect(state.reading_mode).toBe(true);
        });

        it("Given a widget with state initialisation, Then we change rest_error, state must change too", () => {
            mutations.setErrorMessage(state, "oui");
            expect(state.error_message).toEqual("oui");
        });

        it("Given a widget with state initialisation, Then we change isLoading, state must change too", () => {
            mutations.setIsLoading(state, true);
            expect(state.is_loading).toBe(true);
        });

        it("Given a widget with state initialisation, Then we call setAddMode, states must change", () => {
            mutations.setAddMode(state, true);
            expect(state.is_add_mode).toBe(true);
            expect(state.rest_feedback.message).toEqual(null);
            expect(state.rest_feedback.type).toEqual(null);
        });

        it("Given a widget with states updated with error message, Then we call setAddMode, states must change", () => {
            state.rest_feedback.message = REST_FEEDBACK_ADD;
            state.rest_feedback.type = "success";
            mutations.setAddMode(state, true);

            expect(state.is_add_mode).toBe(true);
            expect(state.rest_feedback.message).toEqual("");
            expect(state.rest_feedback.type).toEqual("");
        });

        it("Given a widget with states updated with error message, Then we call setAddMode, states must change", () => {
            state.is_add_mode = true;
            state.rest_feedback.message = ERROR_OCCURRED;
            state.rest_feedback.type = "danger";
            mutations.setAddMode(state, false);

            expect(state.is_add_mode).toBe(false);
            expect(state.rest_feedback.message).toEqual("");
            expect(state.rest_feedback.type).toEqual("");
        });

        it("Given a widget with states updated with error message, Then we call replaceCurrentTime, states must change", () => {
            state.current_times = [
                {
                    artifact: {},
                    project: {},
                    id: 1,
                    minutes: 20
                }
            ];
            const updated_time = {
                artifact: {},
                project: {},
                id: 1,
                minutes: 40
            };
            mutations.replaceInCurrentTimes(state, [updated_time, REST_FEEDBACK_EDIT]);
            expect(state.current_times).toEqual([updated_time]);
            expect(state.rest_feedback.message).toEqual(REST_FEEDBACK_EDIT);
            expect(state.rest_feedback.type).toEqual("success");
        });
    });
});
