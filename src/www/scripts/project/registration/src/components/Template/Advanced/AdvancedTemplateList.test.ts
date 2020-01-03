/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

import { createProjectRegistrationLocalVue } from "../../../helpers/local-vue-for-tests";
import AdvancedTemplateList from "./AdvancedTemplateList.vue";
import { shallowMount, Wrapper } from "@vue/test-utils";
import { TemplateData } from "../../../type";
import * as rest_querier from "../../../api/rest-querier";
import { mockFetchSuccess } from "../../../../../../../themes/common/tlp/mocks/tlp-fetch-mock-helper";

describe("AdvancedTemplateList", () => {
    let wrapper: Wrapper<AdvancedTemplateList>;

    it("Display the description by default", async () => {
        wrapper = shallowMount(AdvancedTemplateList, {
            localVue: await createProjectRegistrationLocalVue()
        });

        expect(wrapper.find("[data-test=user-project-description]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=user-project-spinner]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=user-project-error]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=user-project-list]").exists()).toBeFalsy();
    });

    it(`Display spinner when project list is loading`, async () => {
        wrapper = shallowMount(AdvancedTemplateList, {
            localVue: await createProjectRegistrationLocalVue()
        });

        wrapper.vm.$data.is_loading_project_list = true;

        expect(wrapper.find("[data-test=user-project-description]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=user-project-spinner]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=user-project-error]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=user-project-list]").exists()).toBeFalsy();
    });

    it(`Does not display spinner if an error happened`, async () => {
        wrapper = shallowMount(AdvancedTemplateList, {
            localVue: await createProjectRegistrationLocalVue()
        });

        wrapper.vm.$data.is_loading_project_list = true;
        wrapper.vm.$data.has_error = true;

        expect(wrapper.find("[data-test=user-project-description]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=user-project-spinner]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=user-project-error]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=user-project-list]").exists()).toBeFalsy();
    });

    it(`Display error if something went wrong`, async () => {
        wrapper = shallowMount(AdvancedTemplateList, {
            localVue: await createProjectRegistrationLocalVue()
        });

        wrapper.vm.$data.has_error = true;

        expect(wrapper.find("[data-test=user-project-description]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=user-project-spinner]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=user-project-error]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=user-project-list]").exists()).toBeFalsy();
    });

    it(`Load and display the project list if user had already loaded it`, async () => {
        const is_user_admin_of = jest.spyOn(rest_querier, "getProjectUserIsAdminOf");
        mockFetchSuccess(is_user_admin_of, { return_json: {} });

        wrapper = shallowMount(AdvancedTemplateList, {
            localVue: await createProjectRegistrationLocalVue()
        });

        wrapper.find("[data-test=project-load-user-project-list").trigger("click");

        expect(is_user_admin_of).toHaveBeenCalled();

        await wrapper.vm.$nextTick();

        expect(wrapper.find("[data-test=user-project-description]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=user-project-spinner]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=user-project-error]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=user-project-list]").exists()).toBeTruthy();
    });

    it(`Does not load twice the project list`, async () => {
        wrapper = shallowMount(AdvancedTemplateList, {
            localVue: await createProjectRegistrationLocalVue()
        });

        const project: TemplateData = {
            title: "My B project",
            description: "",
            id: "102",
            glyph: "",
            is_built_in: false
        };

        wrapper.vm.$data.project_list = [project];

        const is_user_admin_of = jest.spyOn(rest_querier, "getProjectUserIsAdminOf");
        expect(is_user_admin_of).not.toHaveBeenCalled();
    });
});
