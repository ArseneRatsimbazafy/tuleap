<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
 * Copyright (C) 2010 Christopher Han <xiphux@gmail.com>
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

namespace Tuleap\Git\GitPHP;

use GeSHi;
use Tuleap\Git\Repository\View\LanguageDetectorForPrismJS;
use Tuleap\Layout\IncludeAssets;

/**
 * Blame controller class
 *
 */
class Controller_Blame extends ControllerBase // @codingStandardsIgnoreLine
{
    use \Tuleap\Git\Repository\View\FeatureFlag;

    /**
     * __construct
     *
     * Constructor
     *
     * @access public
     * @return controller
     */
    public function __construct()
    {
        parent::__construct();
        if (!$this->project) {
            throw new MessageException(dgettext("gitphp", 'Project is required'), true);
        }
    }

    /**
     * GetTemplate
     *
     * Gets the template for this controller
     *
     * @access protected
     * @return string template filename
     */
    protected function GetTemplate() // @codingStandardsIgnoreLine
    {
        if (isset($this->params['js']) && $this->params['js']) {
            return 'blamedata.tpl';
        }
        if ($this->isTuleapBeauGitActivated()) {
            return 'tuleap/blame.tpl';
        }
        return 'blame.tpl';
    }

    /**
     * GetName
     *
     * Gets the name of this controller's action
     *
     * @access public
     * @param boolean $local true if caller wants the localized action name
     * @return string action name
     */
    public function GetName($local = false) // @codingStandardsIgnoreLine
    {
        if ($local) {
            return dgettext("gitphp", 'blame');
        }
        return 'blame';
    }

    /**
     * ReadQuery
     *
     * Read query into parameters
     *
     * @access protected
     */
    protected function ReadQuery() // @codingStandardsIgnoreLine
    {
        if (isset($_GET['hb'])) {
            $this->params['hashbase'] = $_GET['hb'];
        } else {
            $this->params['hashbase'] = 'HEAD';
        }
        if (isset($_GET['f'])) {
            $this->params['file'] = $_GET['f'];
        }
        if (isset($_GET['h'])) {
            $this->params['hash'] = $_GET['h'];
        }
        if (isset($_GET['o']) && ($_GET['o'] == 'js')) {
            $this->params['js'] = true;
        }
    }

    /**
     * LoadData
     *
     * Loads data for this template
     *
     * @access protected
     */
    protected function LoadData() // @codingStandardsIgnoreLine
    {
        $head = $this->project->GetHeadCommit();
        $this->tpl->assign('head', $head);

        $commit = $this->project->GetCommit($this->params['hashbase']);
        $this->tpl->assign('commit', $commit);

        if ((!isset($this->params['hash'])) && (isset($this->params['file']))) {
            $this->params['hash'] = $commit->PathToHash($this->params['file']);
        }

        $blob = $this->project->GetBlob($this->params['hash']);
        if (! $blob) {
            throw new NotFoundException();
        }

        if ($this->params['file']) {
            $blob->SetPath($this->params['file']);
        }
        $blob->SetCommit($commit);
        $this->tpl->assign('blob', $blob);

        $blame = $blob->GetBlame();
        $this->tpl->assign('blame', $blob->GetBlame());

        if (isset($this->params['js']) && $this->params['js']) {
            return;
        }

        $pathtree = [];
        $path = dirname($blob->GetPath());
        while ($path !== '.') {
            $name = basename($path);
            $pathtreepiece = new \stdClass();
            $pathtreepiece->name = $name;
            $pathtreepiece->path = $path;
            $pathtree[] = $pathtreepiece;

            $path = dirname($path);
        };
        $this->tpl->assign('pathtree', array_reverse($pathtree));
        $this->tpl->assign('tree', $commit->GetTree());

        if ($this->isTuleapBeauGitActivated()) {
            $detector = new LanguageDetectorForPrismJS();
            $this->tpl->assign('language', $detector->getLanguageFromExtension(substr(strrchr($blob->GetName(), '.'), 1)));
            $this->tpl->assign('bloblines', $blob->GetData(true));
            $include_assets = new IncludeAssets(__DIR__ . '/../../../www/assets', GIT_BASE_URL . '/assets');
            $GLOBALS['Response']->includeFooterJavascriptFile(
                $include_assets->getFileURL('repository-blob.js')
            );
            return;
        }

        $geshi = new GeSHi("", 'php');
        $lang = $geshi->get_language_name_from_extension(substr(strrchr($blob->GetName(), '.'), 1));
        $geshi->enable_classes();
        $geshi->enable_strict_mode(GESHI_MAYBE);
        $geshi->set_source($blob->GetData());
        $geshi->set_language($lang);
        $geshi->set_header_type(GESHI_HEADER_PRE_TABLE);
        $geshi->enable_line_numbers(GESHI_NORMAL_LINE_NUMBERS);
        $geshi->set_overall_id('git-repository-blame-file');
        $output = $geshi->parse_code();

        $bodystart = strpos($output, '<td');
        $bodyend   = strrpos($output, '</tr>');

        if (($bodystart !== false) && ($bodyend !== false)) {
            $geshihead = substr($output, 0, $bodystart);
            $geshifoot = substr($output, $bodyend);
            $geshibody = substr($output, $bodystart, $bodyend);

            $this->tpl->assign('geshihead', $geshihead);
            $this->tpl->assign('geshibody', $geshibody);
            $this->tpl->assign('geshifoot', $geshifoot);
            $this->tpl->assign('extracss', $geshi->get_stylesheet());
            $this->tpl->assign('geshi', true);
        }
    }
}
