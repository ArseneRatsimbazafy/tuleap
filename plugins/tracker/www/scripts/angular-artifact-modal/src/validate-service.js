/*
 * Copyright (c) Enalean, 2017-present. All Rights Reserved.
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

import _ from "lodash";
import { validateOpenListFieldValue } from "./tuleap-artifact-modal-fields/open-list-field/open-list-field-validate-service.js";
import { formatComputedFieldValue } from "./tuleap-artifact-modal-fields/computed-field/computed-field-value-formatter.js";
import { formatPermissionFieldValue } from "./tuleap-artifact-modal-fields/permission-field/permission-field-value-formatter.js";

export default ValidateService;

ValidateService.$inject = [];

function ValidateService() {
    return {
        validateArtifactFieldsValues: validateArtifactFieldsValues
    };

    function validateArtifactFieldsValues(field_values, creation_mode) {
        var validated_values = _(field_values)
            .filter(function(field) {
                return filterFieldPermissions(field, creation_mode);
            })
            .map(function(field) {
                switch (field.type) {
                    case "computed":
                        return formatComputedFieldValue(field);
                    case "perm":
                        return formatPermissionFieldValue(field);
                    case "tbl":
                        return validateOpenListFieldValue(field);
                    default:
                        return validateOtherFields(field);
                }
            })
            .compact()
            .value();
        return validated_values;
    }

    function filterFieldPermissions(field, creation_mode) {
        if (field === undefined) {
            return false;
        }
        var necessary_permission = creation_mode ? "create" : "update";
        return _(field.permissions).contains(necessary_permission);
    }

    function validateOtherFields(field) {
        if (!filterAtLeastOneAttribute(field)) {
            return;
        }
        if (!filterEmptyFileFieldValue(field)) {
            return;
        }

        if (field.value !== undefined) {
            field = validateValue(field);
        } else if (_.isArray(field.bind_value_ids)) {
            field.bind_value_ids = _.compact(field.bind_value_ids);
        } else if (field.links !== undefined) {
            field = buildLinks(field);
        }

        return removeUnusedAttributes(field);
    }

    function filterAtLeastOneAttribute(field) {
        if (field === undefined) {
            return false;
        }

        var value_defined = field.value !== undefined;
        var bind_value_ids_present = Boolean(field.bind_value_ids);
        var links_present = Boolean(field.links);

        // This is a logical XOR: only one of those 3 attributes may be present at the same time on a given field
        return (
            (value_defined && !bind_value_ids_present && !links_present) ||
            (!value_defined && bind_value_ids_present && !links_present) ||
            (!value_defined && !bind_value_ids_present && links_present)
        );
    }

    function validateValue(field) {
        switch (field.type) {
            case "date":
            case "int":
            case "float":
            case "string":
                if (field.value === null) {
                    field.value = "";
                }
                break;
            default:
                break;
        }
        return field;
    }

    function filterEmptyFileFieldValue(field) {
        if (field.type !== "file") {
            return true;
        }

        return !_.isEmpty(field.value);
    }

    function buildLinks(field) {
        // Merge the text field with the selectbox to create the list of links
        if (_.isString(field.unformatted_links)) {
            var ids = field.unformatted_links.split(",");
            var objects = _.map(ids, function(link_id) {
                return { id: parseInt(link_id, 10) };
            });
            field.links = field.links.concat(objects);
            field.unformatted_links = undefined;
        }
        // Then, filter out all the invalid id values (null, undefined, etc)
        field.links = _.filter(field.links, function(link) {
            return Boolean(link.id);
        });
        return field;
    }

    function removeUnusedAttributes(field) {
        var attributes_to_keep = _.pick(field, function(property, key) {
            switch (key) {
                case "bind_value_ids":
                case "field_id":
                case "links":
                case "value":
                    return !_.isUndefined(property);
                default:
                    return false;
            }
        });
        return attributes_to_keep;
    }
}
