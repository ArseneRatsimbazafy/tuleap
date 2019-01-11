<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

use Tuleap\Docman\ExternalLinks\ExternalLinkRedirector;
use Tuleap\Docman\ExternalLinks\ExternalLinksManager;
use Tuleap\Docman\ExternalLinks\Link;
use Tuleap\Document\Tree\DocumentTreeController;
use Tuleap\Request\CollectRoutesEvent;

require_once __DIR__ . '/../../docman/include/docmanPlugin.class.php';
require_once __DIR__ . '/../vendor/autoload.php';

class documentPlugin extends Plugin // phpcs:ignore
{
    public function __construct($id)
    {
        parent::__construct($id);
        $this->setScope(self::SCOPE_PROJECT);

        bindtextdomain('tuleap-document', __DIR__.'/../site-content');
    }

    public function getHooksAndCallbacks()
    {
        $this->addHook(CollectRoutesEvent::NAME);
        $this->addHook(ExternalLinksManager::NAME);
        $this->addHook(ExternalLinkRedirector::NAME);

        return parent::getHooksAndCallbacks();
    }

    /**
     * @return PluginInfo
     */
    public function getPluginInfo()
    {
        if (! $this->pluginInfo) {
            $this->pluginInfo = new \Tuleap\Document\Plugin\PluginInfo($this);
        }

        return $this->pluginInfo;
    }

    public function getDependencies()
    {
        return ['docman'];
    }

    public function collectRoutesEvent(CollectRoutesEvent $event)
    {
        $event->getRouteCollector()->addGroup('/plugins/document', function (FastRoute\RouteCollector $r) {
            $r->get('/{project_name:[A-z0-9-]+}/[{vue-routing:.*}]', function () {
                return new DocumentTreeController(ProjectManager::instance());
            });
        });
    }

    public function externalLinksManager(ExternalLinksManager $collector)
    {
        if (! PluginManager::instance()->isPluginAllowedForProject($this, $collector->getProjectId())) {
            return;
        }

        $project = ProjectManager::instance()->getProject($collector->getProjectId());

        $collector->addExternalLink(new Link($project, $collector->getFolderId()));
    }

    public function externalLinkRedirector(ExternalLinkRedirector $external_link_redirector)
    {
        $project_id = $external_link_redirector->getProject()->getID();
        if (! PluginManager::instance()->isPluginAllowedForProject($this, $project_id)) {
            return;
        }

        $external_link_redirector->checkAndStoreIfUserHasToBeenRedirected();
    }
}
