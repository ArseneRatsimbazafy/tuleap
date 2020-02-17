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

import { shallowMount, Wrapper } from "@vue/test-utils";
import { createStoreMock } from "../../../../../../../../src/www/scripts/vue-components/store-wrapper-jest";
import { createTrackerCreationLocalVue } from "../../../helpers/local-vue-for-tests";
import StepNavigationButtons from "./StepNavigationButtons.vue";
import VueRouter from "vue-router";
import { createRouter } from "../../../router";

describe("StepNavigationButtons", () => {
    async function getWrapper(
        props: {},
        is_ready_for_step_2 = true
    ): Promise<Wrapper<StepNavigationButtons>> {
        const router: VueRouter = createRouter("my-project");

        jest.spyOn(router, "push").mockImplementation();

        return shallowMount(StepNavigationButtons, {
            mocks: {
                $store: createStoreMock({
                    getters: {
                        is_ready_for_step_2
                    }
                })
            },
            propsData: {
                ...props
            },
            localVue: await createTrackerCreationLocalVue(),
            router
        });
    }

    it("Does not display the [<- back] button when there is no previous step", async () => {
        const wrapper = await getWrapper({
            nextStepName: "step-2"
        });

        expect(wrapper.find("[data-test=button-next]").exists()).toBe(true);
        expect(wrapper.find("[data-test=button-back]").exists()).toBe(false);
    });

    it("Does not display the [next ->] button when there is no next step", async () => {
        const wrapper = await getWrapper({
            previousStepName: "step-1"
        });

        expect(wrapper.find("[data-test=button-next]").exists()).toBe(false);
        expect(wrapper.find("[data-test=button-back]").exists()).toBe(true);
    });

    it("Disables the [next ->] button when the creation is not ready for the step 2 and to click on it does nothing", async () => {
        const wrapper = await getWrapper({ nextStepName: "step-2" }, false);
        const next_step_button = wrapper.find("[data-test=button-next]");

        expect(next_step_button.attributes("disabled")).toBe("disabled");

        next_step_button.trigger("click");

        expect(wrapper.vm.$router.push).not.toHaveBeenCalled();
    });

    it("Clicking on the [next ->] button makes the app navigate to the next step", async () => {
        const wrapper = await getWrapper({ nextStepName: "step-2" }, true);

        wrapper.find("[data-test=button-next]").trigger("click");

        expect(wrapper.vm.$router.push).toHaveBeenCalledWith({ name: "step-2" });
    });
});