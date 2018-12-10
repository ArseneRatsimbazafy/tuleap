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
 *
 */

namespace Tuleap\Request;

use Codendi_HTMLPurifier;
use ConfigDao;
use EventManager;
use FastRoute;
use Tuleap\Admin\ProjectCreation\ProjectCategoriesDisplayController;
use Tuleap\Admin\ProjectCreation\ProjectFieldsDisplayController;
use Tuleap\Admin\ProjectCreation\ProjectFieldsUpdateController;
use Tuleap\admin\ProjectCreation\ProjectVisibility\ProjectVisibilityConfigDisplayController;
use Tuleap\admin\ProjectCreation\ProjectVisibility\ProjectVisibilityConfigManager;
use Tuleap\admin\ProjectCreation\ProjectVisibility\ProjectVisibilityConfigUpdateController;
use Tuleap\Admin\ProjectCreation\WebhooksDisplayController;
use Tuleap\Admin\ProjectCreation\WebhooksUpdateController;
use Tuleap\Admin\ProjectCreationModerationDisplayController;
use Tuleap\Admin\ProjectCreationModerationUpdateController;
use Tuleap\Admin\ProjectTemplatesController;
use Tuleap\Error\PermissionDeniedMailSender;
use Tuleap\Error\PlaceHolderBuilder;
use Tuleap\Layout\LegacySiteHomePageController;
use Tuleap\Layout\SiteHomepageController;
use Tuleap\Password\Administration\PasswordPolicyDisplayController;
use Tuleap\Password\Administration\PasswordPolicyUpdateController;
use Tuleap\Password\Configuration\PasswordConfigurationDAO;
use Tuleap\Password\Configuration\PasswordConfigurationRetriever;
use Tuleap\Password\Configuration\PasswordConfigurationSaver;
use Tuleap\Trove\TroveCatListController;
use Tuleap\User\AccessKey\AccessKeyCreationController;
use Tuleap\User\AccessKey\AccessKeyRevocationController;
use Tuleap\User\Account\ChangeAvatarController;
use Tuleap\User\Account\UserAvatarSaver;
use Tuleap\User\Profile\AvatarController;
use Tuleap\User\Profile\ProfileController;
use Tuleap\User\Profile\ProfilePresenterBuilder;

class RouteCollector
{
    /**
     * @var EventManager
     */
    private $event_manager;

    public function __construct(EventManager $event_manager)
    {
        $this->event_manager = $event_manager;
    }

    public function collect(FastRoute\RouteCollector $r)
    {
        $r->get('/', function () {
            $dao = new \Admin_Homepage_Dao();
            if ($dao->isStandardHomepageUsed()) {
                return new SiteHomepageController();
            }
            return new LegacySiteHomePageController();
        });
        $r->addRoute(['GET', 'POST'], '/projects/{name}[/]', function () {
            return new \Tuleap\Project\Home();
        });
        $r->addGroup('/admin', function (FastRoute\RouteCollector $r) {
            $r->get('/password_policy/', function () {
                return new PasswordPolicyDisplayController(
                    new \Tuleap\Admin\AdminPageRenderer,
                    \TemplateRendererFactory::build(),
                    new PasswordConfigurationRetriever(new PasswordConfigurationDAO)
                );
            });
            $r->post('/password_policy/', function () {
                return new PasswordPolicyUpdateController(
                    new PasswordConfigurationSaver(new PasswordConfigurationDAO)
                );
            });
            $r->get('/project-creation/moderation', function () {
                return new ProjectCreationModerationDisplayController();
            });
            $r->post('/project-creation/moderation', function () {
                return new ProjectCreationModerationUpdateController();
            });
            $r->get('/project-creation/templates', function () {
                return new ProjectTemplatesController();
            });
            $r->get('/project-creation/webhooks', function () {
                return new WebhooksDisplayController();
            });
            $r->post('/project-creation/webhooks', function () {
                return new WebhooksUpdateController();
            });
            $r->get('/project-creation/fields', function () {
                return new ProjectFieldsDisplayController();
            });
            $r->post('/project-creation/fields', function () {
                return new ProjectFieldsUpdateController();
            });
            $r->get('/project-creation/categories', function () {
                return new ProjectCategoriesDisplayController();
            });
            $r->post('/project-creation/categories', function () {
                return new TroveCatListController();
            });
            $r->get('/project-creation/visibility', function () {
                return new ProjectVisibilityConfigDisplayController();
            });
            $r->post('/project-creation/visibility', function () {
                return new ProjectVisibilityConfigUpdateController(
                    new ProjectVisibilityConfigManager(
                        new ConfigDao()
                    )
                );
            });
        });
        $r->addGroup('/account', function (FastRoute\RouteCollector $r) {
            $r->post('/access_key/create', function () {
                return new AccessKeyCreationController();
            });
            $r->post('/access_key/revoke', function () {
                return new AccessKeyRevocationController();
            });
        });
        $r->post('/account/avatar', function () {
            $user_manager = \UserManager::instance();
            return new ChangeAvatarController($user_manager, new UserAvatarSaver($user_manager));
        });

        $r->addRoute(['GET'], '/users/{name}[/]', function () {
            return new ProfileController(
                new ProfilePresenterBuilder(EventManager::instance(), Codendi_HTMLPurifier::instance())
            );
        });

        $r->addRoute(['GET'], '/users/{name}/avatar.png', function () {
            return new AvatarController();
        });

        $r->addRoute(['GET'], '/users/{name}/avatar-{hash}.png', function () {
            return new AvatarController(['expires' => 'never']);
        });

        $r->addRoute(['POST'], '/join-private-project-mail/', function () {
            return new PermissionDeniedMailSender(
                new PlaceHolderBuilder(\ProjectManager::instance()),
                new \CSRFSynchronizerToken("/join-private-project-mail/")
            );
        });

        $r->addRoute(['POST'], '/join-project-restricted-user-mail/', function () {
            return new PermissionDeniedMailSender(
                new PlaceHolderBuilder(\ProjectManager::instance()),
                new \CSRFSynchronizerToken("/join-project-restricted-user-mail/")
            );
        });

        $collect_routes = new CollectRoutesEvent($r);
        $this->event_manager->processEvent($collect_routes);
    }
}
