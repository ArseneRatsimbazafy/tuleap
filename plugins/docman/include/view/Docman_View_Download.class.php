<?php
/**
 * Copyright (c) Enalean 2014-2019. All rights reserved
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

use Tuleap\Http\BinaryFileResponse;

require_once('Docman_View_View.class.php');
require_once('Docman_View_DocmanError.class.php');

class Docman_View_Download extends Docman_View_View {
    
    /* protected */ function _content($params) {
        if (isset($params['version_number'])) {
            $version_factory = $this->_getVersionFactory($params);
            $version = $version_factory->getSpecificVersion($params['item'], $params['version_number']);
        } else {
            $version = $params['item']->getCurrentVersion();
        }
        if ($version) {
            if (file_exists($version->getPath())) {
                try {
                    $version->preDownload($params['item'], $params['user']);
                } catch (Exception $e) {
                    $GLOBALS['Response']->addFeedback('error',$e->getMessage(), CODENDI_PURIFIER_DISABLED);
                    $GLOBALS['Response']->redirect($this->_controller->getDefaultUrl());
                }
                $file_response = New BinaryFileResponse(
                    $version->getPath(),
                    $version->getFilename(),
                    $version->getFiletype()
                );
                $file_response->send();
            } else {
                $this->_controller->feedback->log('error', 'The file cannot be found.');
                $v = new Docman_View_DocmanError($this->_controller);
                $v->display($params);
            }
        }
    }
}
