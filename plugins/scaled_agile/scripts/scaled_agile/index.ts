/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

import Vue from "vue";
import App from "./src/components/App.vue";
import {
    initVueGettext,
    getPOFileFromLocale,
} from "@tuleap/core/scripts/tuleap/gettext/vue-gettext-init";
import { build } from "./src/configuration";

document.addEventListener("DOMContentLoaded", async () => {
    const vue_mount_point = document.getElementById("scaled-agile");
    if (!vue_mount_point) {
        return;
    }

    const locale = document.body.dataset.userLocale;
    if (locale === undefined) {
        throw new Error("Unable to load user locale");
    }

    Vue.config.language = locale;

    if (!vue_mount_point.dataset.projectName) {
        throw new Error("Missing projectName dataset");
    }
    const project_name = vue_mount_point.dataset.projectName;

    if (!vue_mount_point.dataset.projectShortName) {
        throw new Error("Missing projectShortName dataset");
    }
    const project_short_name = vue_mount_point.dataset.projectShortName;

    if (!vue_mount_point.dataset.projectPrivacy) {
        throw new Error("Missing projectPrivacy dataset");
    }
    const project_privacy = JSON.parse(vue_mount_point.dataset.projectPrivacy);

    if (!vue_mount_point.dataset.projectFlags) {
        throw new Error("Missing projectFlags dataset");
    }
    const project_flags = JSON.parse(vue_mount_point.dataset.projectFlags);

    if (!vue_mount_point.dataset.programId) {
        throw new Error("Missing program_id dataset");
    }
    const program_id = parseInt(vue_mount_point.dataset.programId, 10);

    if (!vue_mount_point.dataset.programIncrementTrackerId) {
        throw new Error("Missing program_increment_tracker_id dataset");
    }
    const program_increment_tracker_id = parseInt(
        vue_mount_point.dataset.programIncrementTrackerId,
        10
    );

    if (!vue_mount_point.dataset.userWithAccessibilityMode) {
        throw new Error("Missing accessiblity dataset");
    }
    const accessibility = Boolean(vue_mount_point.dataset.userWithAccessibilityMode);

    const can_create_program_increment = Boolean(vue_mount_point.dataset.canCreateProgramIncrement);

    build(
        project_name,
        project_short_name,
        project_privacy,
        project_flags,
        program_id,
        accessibility,
        locale.replace("_", "-"),
        can_create_program_increment,
        program_increment_tracker_id
    );

    await initVueGettext(
        Vue,
        (locale: string) =>
            import(/* webpackChunkName: "scaled-agile-po-" */ "./po/" + getPOFileFromLocale(locale))
    );

    const AppComponent = Vue.extend(App);

    new AppComponent({}).$mount(vue_mount_point);
});
