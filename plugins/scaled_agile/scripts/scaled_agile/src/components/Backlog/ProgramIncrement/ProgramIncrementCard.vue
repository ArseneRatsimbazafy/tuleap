<!---
  - Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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
    <div class="tlp-pane-container program-increment-display">
        <div
            class="tlp-pane-header program-increment-header"
            v-on:click="toggleIsOpen"
            data-test="program-increment-toggle"
        >
            <span class="tlp-pane-title program-increment-header-label">
                <i
                    class="tlp-pane-title-icon fas fa-fw"
                    v-bind:class="[is_open ? 'fa-caret-down' : 'fa-caret-right']"
                    data-test="program-increment-toggle-icon"
                    aria-hidden="true"
                />
                {{ increment.title }}
            </span>
            <div class="program-increment-header-spacer"></div>
            <span class="program-increment-header-dates" v-if="increment.start_date !== null">
                {{ formatDate(increment.start_date) }}
                <i class="fas fa-long-arrow-alt-right" aria-hidden="true"></i>
                {{ formatDate(increment.end_date) }}
            </span>
            <span class="tlp-badge-outline tlp-badge-primary">{{ increment.status }}</span>
        </div>
        <div
            class="program-increment-info"
            v-bind:class="{ 'program-increment-info-hidden': !is_open }"
            data-test="program-increment-info"
        >
            <a
                v-bind:href="`/plugins/tracker/?aid=${increment.id}`"
                class="tlp-button-primary tlp-button-outline tlp-button-mini"
                v-bind:title="$gettext('Edit')"
            >
                <i class="fas fa-pencil-alt tlp-button-icon" aria-hidden="true"></i>
                <span v-translate>Edit</span>
            </a>
        </div>
        <section
            class="tlp-pane-section program-increment-content"
            v-bind:class="{ 'program-increment-content-hidden': !is_open }"
            data-test="program-increment-content"
        >
            <div class="program-increment-content-items">
                <program-increment-no-content />
            </div>
        </section>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import ProgramIncrementNoContent from "./ProgramIncrementNoContent.vue";
import { formatDateYearMonthDay } from "@tuleap/date-helper";
import { getUserLocale } from "../../../configuration";

@Component({
    components: { ProgramIncrementNoContent },
})
export default class ProgramIncrement extends Vue {
    @Prop({ required: true })
    readonly increment!: ProgramIncrement;

    is_open = false;

    formatDate = (date: string): string => formatDateYearMonthDay(getUserLocale(), date);

    toggleIsOpen(): void {
        this.is_open = !this.is_open;
    }
}
</script>
