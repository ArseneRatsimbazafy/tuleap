/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

import { modal as createModal, datePicker } from "tlp";

document.addEventListener("DOMContentLoaded", () => {
    handleSSHKeys();
    handleAccessKeys();
    handleSVNTokens();
});

function handleSSHKeys(): void {
    addSSHKeyButton();

    toggleButtonAccordingToCheckBoxesStateWithIds("remove-ssh-keys-button", "ssh_key_selected[]");

    const ssh_key = document.getElementById("ssh-key");
    if (!(ssh_key instanceof HTMLTextAreaElement)) {
        throw new Error("#ssh-key not found or is not a textarea");
    }
    const button = document.getElementById("submit-new-ssh-key-button");
    if (!(button instanceof HTMLButtonElement)) {
        throw new Error("#submit-new-ssh-key-button not found or is not a button");
    }
    changeButtonStatusDependingTextareaStatus(button, ssh_key);

    const ssh_keys_list = document.querySelectorAll<HTMLElement>("[data-ssh_key_value]");
    ssh_keys_list.forEach(row => {
        row.addEventListener("click", () => {
            const full_ssh_key = row.getAttribute("data-ssh_key_value");
            if (!full_ssh_key) {
                return;
            }
            row.innerText = full_ssh_key;
            row.className = "ssh-key-value-reset-cursor";
        });
    });
}

function addSSHKeyButton(): void {
    const button = document.getElementById("add-ssh-key-button");

    if (!(button instanceof HTMLButtonElement)) {
        throw new Error("#add-ssh-key-button not found or is not a button");
    }

    popupModal(button);
}

function handleAccessKeys(): void {
    addAccessKeyButton();
    addAccessKeyDatePicker();

    toggleButtonAccordingToCheckBoxesStateWithIds(
        "button-revoke-access-tokens",
        "access-keys-selected[]"
    );
    toggleButtonAccordingToCheckBoxesStateWithIds(
        "generate-new-access-key-button",
        "access-key-scopes[]"
    );
}

function addAccessKeyButton(): void {
    const button = document.getElementById("generate-access-key-button");

    if (!(button instanceof HTMLButtonElement)) {
        throw new Error("#generate-access-key-button not found or is not a button");
    }

    popupModal(button);
}

function addAccessKeyDatePicker(): void {
    const date_picker = document.getElementById("access-key-expiration-date-picker");

    if (!date_picker) {
        return;
    }

    datePicker(date_picker);
}

function handleSVNTokens(): void {
    addSVNTokenButton();

    toggleButtonAccordingToCheckBoxesStateWithIds(
        "button-revoke-svn-tokens",
        "svn-tokens-selected[]"
    );
}

function addSVNTokenButton(): void {
    const button = document.getElementById("generate-svn-token-button");

    if (!(button instanceof HTMLButtonElement)) {
        throw new Error("#generate-svn-token-button not found or is not a button");
    }

    popupModal(button);
}

function popupModal(button: HTMLButtonElement): void {
    if (button && button.dataset) {
        const modal_target_id = button.dataset.targetModalId;

        if (!modal_target_id) {
            return;
        }

        const modal_element = document.getElementById(modal_target_id);
        if (!modal_element) {
            return;
        }
        const modal = createModal(modal_element);

        button.addEventListener("click", () => {
            modal.show();
        });
    }
}

function toggleButtonAccordingToCheckBoxesStateWithIds(
    button_id: string,
    checkbox_name: string
): void {
    const button = document.getElementById(button_id);

    const checkboxes = [...document.getElementsByName(checkbox_name)].filter(
        (element): element is HTMLInputElement => element instanceof HTMLInputElement
    );

    if (!(button instanceof HTMLButtonElement)) {
        return;
    }

    toggleButtonAccordingToCheckBoxesState(button, checkboxes);
}

function toggleButtonAccordingToCheckBoxesState(
    button: HTMLButtonElement,
    checkboxes: HTMLInputElement[]
): void {
    changeButtonStatusDependingCheckboxesStatus(button, checkboxes);

    checkboxes.forEach(function(checkbox) {
        checkbox.addEventListener("change", () => {
            changeButtonStatusDependingCheckboxesStatus(button, checkboxes);
        });
    });
}

function changeButtonStatusDependingCheckboxesStatus(
    button: HTMLButtonElement,
    checkboxes: HTMLInputElement[]
): void {
    const at_least_one_checkbox_is_checked = checkboxes.some(checkbox => checkbox.checked);

    if (at_least_one_checkbox_is_checked) {
        button.disabled = false;
    } else {
        button.disabled = true;
    }
}

function changeButtonStatusDependingTextareaStatus(
    button: HTMLButtonElement,
    textarea: HTMLTextAreaElement
): void {
    textarea.addEventListener("input", () => {
        const text = textarea.value;
        if (!text) {
            return;
        }

        if (text.trim() === "") {
            button.disabled = true;
        } else {
            button.disabled = false;
        }
    });
}
