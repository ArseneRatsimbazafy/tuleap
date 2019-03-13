<?php
/**
 * Copyright (c) Enalean, 2013-2018. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2010. All Rights Reserved.
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

require_once 'bootstrap.php';

Mock::generate('BaseLanguage');
Mock::generate('PFUser');
Mock::generate('Project');
Mock::generate('FRSFile');
Mock::generate('WebDAVFRSRelease');
Mock::generate('WebDAVUtils');
Mock::generatePartial(
    'WebDAVFRSFile',
    'WebDAVFRSFileTestVersion',
array('getFileLocation', 'getFile', 'getFileId', 'getProject', 'getUtils', 'logDownload', 'userCanWrite', 'copyFile')
);

/**
 * This is the unit test of WebDAVFRSFile
 */
class WebDAVFRSFileTest extends TuleapTestCase
{

    /**
     * Testing delete when user is not admin
     */
    function testDeleteFailWithUserNotAdmin() {

        $webDAVFile = new WebDAVFRSFileTestVersion($this);
        $webDAVFile->setReturnValue('userCanWrite', false);
        $this->expectException('Sabre_DAV_Exception_Forbidden');

        $webDAVFile->delete();

    }

    /**
     * Testing delete when file doesn't exist
     */
    function testDeleteFileNotExist() {

        $webDAVFile = new WebDAVFRSFileTestVersion($this);
        $webDAVFile->setReturnValue('userCanWrite', true);
        $frsff = \Mockery::mock(FRSFileFactory::class);
        $frsff->shouldReceive('delete_file')->andReturn(0);
        $utils = new MockWebDAVUtils();
        $utils->setReturnValue('getFileFactory', $frsff);
        $project = new MockProject();
        $webDAVFile->setReturnValue('getProject', $project);
        $webDAVFile->setReturnValue('getUtils', $utils);

        $this->expectException('Sabre_DAV_Exception_Forbidden');

        $webDAVFile->delete();

    }

    /**
     * Testing succeeded delete
     */
    function testDeleteSucceede() {

        $webDAVFile = new WebDAVFRSFileTestVersion($this);
        $webDAVFile->setReturnValue('userCanWrite', true);
        $frsff = \Mockery::mock(FRSFileFactory::class);
        $frsff->shouldReceive('delete_file')->andReturn(1);
        $utils = new MockWebDAVUtils();
        $utils->setReturnValue('getFileFactory', $frsff);
        $project = new MockProject();
        $webDAVFile->setReturnValue('getProject', $project);
        $webDAVFile->setReturnValue('getUtils', $utils);

        $webDAVFile->delete();

    }
}
