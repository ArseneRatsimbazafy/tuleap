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

import { KeyboardNavigationManager } from "./KeyboardNavigationManager";
import { BaseComponentRenderer } from "../renderers/BaseComponentRenderer";
import { appendGroupedOptionsToSourceSelectBox } from "../test-helpers/select-box-options-generator";
import { DropdownContentRenderer } from "../renderers/DropdownContentRenderer";
import { generateItemMapBasedOnSourceSelectOptions } from "../helpers/static-list-helper";
import { GetText } from "../../../tuleap/gettext/gettext-init";
import { ListPickerItem } from "../type";
import { ListItemHighlighter } from "./ListItemHighlighter";
import { DropdownToggler } from "../dropdown/DropdownToggler";

describe("KeyboardNavigationManager", () => {
    let manager: KeyboardNavigationManager,
        highlighter: ListItemHighlighter,
        toggler: DropdownToggler,
        dropdown_list: Element,
        item_map: Map<string, ListPickerItem>;

    function getItem(item_id: string): ListPickerItem {
        const item = item_map.get(item_id);
        if (!item) {
            throw new Error("Item not found in map");
        }
        return item;
    }

    function assertOnlyOneItemIsHighlighted(): void {
        expect(dropdown_list.querySelectorAll(".list-picker-item-highlighted").length).toEqual(1);
    }

    beforeEach(() => {
        const source_select_box = document.createElement("select");
        appendGroupedOptionsToSourceSelectBox(source_select_box);

        const {
            list_picker_element,
            dropdown_element,
            dropdown_list_element,
            search_field_element,
            selection_element,
        } = new BaseComponentRenderer(source_select_box).renderBaseComponent();

        item_map = generateItemMapBasedOnSourceSelectOptions(source_select_box);
        const content_renderer = new DropdownContentRenderer(
            source_select_box,
            dropdown_list_element,
            item_map,
            {
                gettext: (english: string) => english,
            } as GetText
        );

        dropdown_list = dropdown_list_element;

        content_renderer.renderListPickerDropdownContent();
        highlighter = new ListItemHighlighter(dropdown_list_element);
        toggler = new DropdownToggler(
            list_picker_element,
            dropdown_element,
            dropdown_list_element,
            search_field_element,
            selection_element
        );
        manager = new KeyboardNavigationManager(dropdown_list_element, toggler, highlighter);

        highlighter.resetHighlight();
    });

    describe("arrows up/down", () => {
        afterEach(() => {
            assertOnlyOneItemIsHighlighted();
        });

        describe("ArrowDown key", () => {
            it("removes the highlight on the previous item and highlights the next one", () => {
                manager.navigate(new KeyboardEvent("keydown", { key: "ArrowDown" }));

                expect(getItem("item-1").element.classList).toContain(
                    "list-picker-item-highlighted"
                );
            });

            it("When the user reaches the last valid item, then it should keep it highlighted", () => {
                manager.navigate(new KeyboardEvent("keydown", { key: "ArrowDown" })); // highlights 2nd
                manager.navigate(new KeyboardEvent("keydown", { key: "ArrowDown" })); // highlights 3rd
                manager.navigate(new KeyboardEvent("keydown", { key: "ArrowDown" })); // highlights 4th
                manager.navigate(new KeyboardEvent("keydown", { key: "ArrowDown" })); // won't highlight 5th since it is disabled

                expect(getItem("item-4").element.classList).toContain(
                    "list-picker-item-highlighted"
                );
            });
        });

        describe("ArrowUp key", () => {
            it("removes the highlight on the next item and highlights the previous one", () => {
                manager.navigate(new KeyboardEvent("keydown", { key: "ArrowDown" })); // highlights 2nd
                manager.navigate(new KeyboardEvent("keydown", { key: "ArrowUp" })); // highlights 1st

                expect(getItem("item-0").element.classList).toContain(
                    "list-picker-item-highlighted"
                );
            });

            it("When the user reaches the first item, then it should keep it highlighted", () => {
                manager.navigate(new KeyboardEvent("keydown", { key: "ArrowDown" })); // highlights 2nd
                manager.navigate(new KeyboardEvent("keydown", { key: "ArrowUp" })); // highlights 1st
                manager.navigate(new KeyboardEvent("keydown", { key: "ArrowUp" })); // can't go upper, highlights 1st
                manager.navigate(new KeyboardEvent("keydown", { key: "ArrowUp" })); // same

                expect(getItem("item-0").element.classList).toContain(
                    "list-picker-item-highlighted"
                );
            });
        });
    });
});