<!--
  - Copyright (c) Enalean, 2019-Present. All Rights Reserved.
  -
  - This file is a part of Tuleap.
  -
  - Tuleap is free software; you can redistribute it and/or modify
  - it under the terms of the GNU General Public License as published by
  - the Free Software Foundation; either version 2 of the License, or
  - (at your option) any later version.
  -
  - Tuleap is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU General Public License for more details.
  -
  - You should have received a copy of the GNU General Public License
  - along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
  -->

<template>
    <div class="artifact-modal-text-label-with-format">
        <label class="tlp-label artifact-modal-text-label" v-bind:for="id">
            {{ label }}
            <i v-if="required" class="fa fa-asterisk artifact-modal-text-asterisk"></i>
        </label>
        <div class="artifact-modal-text-label-with-format-and-helper-container">
            <select
                v-model="format"
                v-bind:disabled="disabled"
                class="tlp-select tlp-select-small tlp-select-adjusted"
                data-test="format"
            >
                <option v-bind:value="text_format">Text</option>
                <option v-bind:value="html_format">HTML</option>
                <option v-bind:value="commonmark_format">Markdown</option>
            </select>
            <commonmark-syntax-helper v-if="is_commonmark_format" />
        </div>
    </div>
</template>
<script>
import {
    TEXT_FORMAT_COMMONMARK,
    TEXT_FORMAT_HTML,
    TEXT_FORMAT_TEXT,
} from "../../../constants/fields-constants.js";
import CommonmarkSyntaxHelper from "./CommonmarkSyntaxHelper.vue";

export default {
    name: "FormatSelector",
    components: { CommonmarkSyntaxHelper },
    props: {
        id: String,
        label: String,
        value: {
            type: String,
            validator(value) {
                return [TEXT_FORMAT_HTML, TEXT_FORMAT_TEXT, TEXT_FORMAT_COMMONMARK].includes(value);
            },
        },
        disabled: Boolean,
        required: Boolean,
    },
    computed: {
        format: {
            get() {
                return this.value;
            },
            set(new_format) {
                this.$emit("input", new_format);
            },
        },
        is_commonmark_format() {
            return this.value === TEXT_FORMAT_COMMONMARK;
        },
        text_format() {
            return TEXT_FORMAT_TEXT;
        },
        html_format() {
            return TEXT_FORMAT_HTML;
        },
        commonmark_format() {
            return TEXT_FORMAT_COMMONMARK;
        },
    },
};
</script>
