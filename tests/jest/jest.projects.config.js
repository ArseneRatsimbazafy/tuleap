/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

const path = require("path");

const base_config = require("./jest.base.config.js");
const tuleap_core_config = require("../../src/jest.config.js");

module.exports = {
    rootDir: path.resolve(__dirname, "../../"),
    projects: [
        "<rootDir>/plugins/**!(node_modules)/jest.config.js",
        "<rootDir>/src/jest.config.js",
        "<rootDir>/src/scripts/lib/**/jest.config.js",
        "<rootDir>/src/scripts/list-picker/jest.config.js",
        "<rootDir>/src/themes/tlp/jest.config.js",
    ],
    collectCoverageFrom: [
        ...base_config.collectCoverageFrom,
        ...tuleap_core_config.collectCoverageFrom,
    ],
};
