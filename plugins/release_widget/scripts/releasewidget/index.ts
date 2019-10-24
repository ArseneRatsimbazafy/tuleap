/*
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

import Vue from "vue";
import VueDOMPurifyHTML from "vue-dompurify-html";
import App from "./src/components/App.vue";
import { createStore } from "./src/store";
import { setUserLocale } from "./src/helpers/user-locale-helper";
import { initVueGettext } from "../../../../src/www/scripts/tuleap/gettext/vue-gettext-init";

document.addEventListener("DOMContentLoaded", async () => {
    Vue.use(VueDOMPurifyHTML);

    const locale = document.body.dataset.userLocale;
    if (locale !== undefined) {
        Vue.config.language = locale;
        setUserLocale(locale.replace("_", "-"));
    }
    await initVueGettext(Vue, (locale: string) =>
        import(/* webpackChunkName: "releasewidget-po-" */ `./po/${locale}.po`)
    );

    const vue_mount_point = document.getElementById("release-widget");

    if (!vue_mount_point) {
        return;
    }

    const project_id_dataset = vue_mount_point.dataset.projectId;
    const is_IE11_dataset = vue_mount_point.dataset.isIe11;
    const nb_upcoming_releases_dataset = vue_mount_point.dataset.nbUpcomingReleases;
    const nb_backlog_items_dataset = vue_mount_point.dataset.nbBacklogItems;

    if (!project_id_dataset) {
        throw new Error("Project Id is missing.");
    }

    if (!nb_upcoming_releases_dataset) {
        throw new Error("Number Upcoming Releases is missing.");
    }

    if (!nb_backlog_items_dataset) {
        throw new Error("Number Backlog Items is missing.");
    }

    const project_id = Number.parseInt(project_id_dataset, 10);
    const nb_upcoming_releases = Number.parseInt(nb_upcoming_releases_dataset, 10);
    const nb_backlog_items = Number.parseInt(nb_backlog_items_dataset, 10);

    const AppComponent = Vue.extend(App);
    const store = createStore();

    new AppComponent({
        store,
        propsData: {
            projectId: project_id,
            isBrowserIE11: is_IE11_dataset,
            nbUpcomingReleases: nb_upcoming_releases,
            nbBacklogItems: nb_backlog_items
        }
    }).$mount(vue_mount_point);
});
