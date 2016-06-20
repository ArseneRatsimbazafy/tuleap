<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

class Git_AdminMirrorController {

    /** @var Git_Mirror_MirrorDataMapper */
    private $git_mirror_mapper;

    /** @var CSRFSynchronizerToken */
    private $csrf;

    /** @var Git_MirrorResourceRestrictor */
    private $git_mirror_resource_restrictor;

    /** @var ProjectManager */
    private $project_manager;

    /** @var Git_Mirror_ManifestManager */
    private $git_mirror_manifest_manager;

    /** @var Git_SystemEventManager */
    private $git_system_event_manager;

    public function __construct(
        CSRFSynchronizerToken $csrf,
        Git_Mirror_MirrorDataMapper $git_mirror_mapper,
        Git_MirrorResourceRestrictor $git_mirror_resource_restrictor,
        ProjectManager $project_manager,
        Git_Mirror_ManifestManager $git_mirror_manifest_manager,
        Git_SystemEventManager $git_system_event_manager
    ) {
        $this->csrf                           = $csrf;
        $this->git_mirror_mapper              = $git_mirror_mapper;
        $this->git_mirror_resource_restrictor = $git_mirror_resource_restrictor;
        $this->project_manager                = $project_manager;
        $this->git_mirror_manifest_manager    = $git_mirror_manifest_manager;
        $this->git_system_event_manager       = $git_system_event_manager;
    }

    public function process(Codendi_Request $request) {
        if ($request->get('action') == 'add-mirror') {
            $this->createMirror($request);
        } elseif ($request->get('action') == 'show-add-mirror') {
            $this->showAddMirror();
        } elseif ($request->get('action') == 'show-edit-mirror') {
            $this->showEditMirror($request);
        } elseif ($request->get('action') == 'modify-mirror' && $request->get('update_mirror')) {
            $this->modifyMirror($request);
        } elseif ($request->get('action') == 'modify-mirror' && $request->get('delete_mirror')) {
            $this->deleteMirror($request);
        } elseif ($request->get('action') == 'set-mirror-restriction') {
            $this->setMirrorRestriction($request);
        } elseif ($request->get('action') == 'update-allowed-project-list') {
            $this->updateAllowedProjectList($request);
        } elseif ($request->get('action') == 'dump-gitolite-conf') {
            $this->askForAGitoliteDumpConf();
        }
    }

    public function display(Codendi_Request $request) {
        $title     = $GLOBALS['Language']->getText('plugin_git', 'descriptor_name');
        $renderer  = TemplateRendererFactory::build()->getRenderer(dirname(GIT_BASE_DIR).'/templates');
        $presenter = null;

        switch ($request->get('action')) {
            case 'list-repositories':
                $presenter = $this->getListRepositoriesPresenter($request);
                break;
            case 'manage-allowed-projects':
            case 'set-mirror-restriction':
            case 'update-allowed-project-list':
                $presenter = $this->getManageAllowedProjectsPresenter($request);
                $renderer = TemplateRendererFactory::build()->getRenderer(ForgeConfig::get('codendi_dir') . '/src/templates/resource_restrictor');
                break;
            case 'show-edit-mirror':
            case 'show-add-mirror':
                break;
            default:
                $presenter = $this->getAllMirrorsPresenter($title);
                break;

        }

        if (! $presenter) {
            return;
        }

        $GLOBALS['HTML']->header(array('title' => $title, 'selected_top_tab' => 'admin', 'main_classes' => array('framed-vertically')));
        $renderer->renderToPage($presenter->getTemplate(), $presenter);
        $GLOBALS['HTML']->footer(array());
    }

    private function getAllMirrorsPresenter($title) {
        return new Git_AdminMirrorListPresenter(
            $title,
            $this->csrf,
            $this->getMirrorPresenters($this->git_mirror_mapper->fetchAll())
        );
    }

    /**
     * @param Git_Mirror_Mirror[] $mirrors
     * @return array
     */
    private function getMirrorPresenters(array $mirrors) {
        $mirror_presenters = array();
        foreach($mirrors as $mirror) {
            $mirror_presenters[] = array(
                'id'                     => $mirror->id,
                'url'                    => $mirror->url,
                'hostname'               => $mirror->hostname,
                'name'                   => $mirror->name,
                'owner_id'               => $mirror->owner_id,
                'owner_name'             => $mirror->owner_name,
                'ssh_key_value'          => $mirror->ssh_key,
                'ssh_key_ellipsis_value' => substr($mirror->ssh_key, 0, 40).'...'.substr($mirror->ssh_key, -40),
            );
        }
        return $mirror_presenters;
    }

    private function getListRepositoriesPresenter(Codendi_Request $request) {
        $mirror_id = $request->get('mirror_id');
        $mirror    = $this->git_mirror_mapper->fetch($mirror_id);

        return new Git_AdminMRepositoryListPresenter(
            $mirror->url,
            $this->git_mirror_mapper->fetchRepositoriesPerMirrorPresenters($mirror)
        );
    }

    private function getManageAllowedProjectsPresenter(Codendi_Request $request) {
        $mirror_id = $request->get('mirror_id');
        $mirror    = $this->git_mirror_mapper->fetch($mirror_id);

        return new Git_AdminMAllowedProjectsPresenter(
            $mirror,
            $this->git_mirror_resource_restrictor->searchAllowedProjectsOnMirror($mirror),
            $this->git_mirror_resource_restrictor->isMirrorRestricted($mirror)
        );
    }

    private function setMirrorRestriction($request) {
        $mirror_id   = $request->get('mirror_id');
        $mirror      = $this->git_mirror_mapper->fetch($mirror_id);
        $all_allowed = $request->get('all-allowed');

        $this->checkSynchronizerToken('/plugins/git/admin/?pane=mirrors_admin&action=set-mirror-restriction&mirror_id=' . $mirror_id);

        if ($all_allowed) {
            if ($this->git_mirror_resource_restrictor->unsetMirrorRestricted($mirror)) {
                $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('plugin_git', 'mirror_allowed_project_unset_restricted'));
                $GLOBALS['Response']->redirect('/plugins/git/admin/?pane=mirrors_admin&action=manage-allowed-projects&mirror_id=' . $mirror_id);
            }

        } else {
            if (
                $this->git_mirror_resource_restrictor->setMirrorRestricted($mirror) &&
                $this->git_mirror_mapper->deleteFromDefaultMirrors($mirror->id)
            ) {
                $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('plugin_git', 'mirror_allowed_project_set_restricted'));
                $GLOBALS['Response']->redirect('/plugins/git/admin/?pane=mirrors_admin&action=manage-allowed-projects&mirror_id=' . $mirror_id);
            }
        }

        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_git', 'mirror_allowed_project_restricted_error'));
        $GLOBALS['Response']->redirect('/plugins/git/admin/?pane=mirrors_admin&action=manage-allowed-projects&mirror_id=' . $mirror_id);
    }

    private function askForAGitoliteDumpConf() {
        $this->csrf->check();

        $this->git_system_event_manager->queueDumpOfAllMirroredRepositories();
        $GLOBALS['Response']->addFeedback(Feedback::INFO, $GLOBALS['Language']->getText('plugin_git','dump_gitolite_conf_queued', array($this->getGitSystemEventsQueueURL())), CODENDI_PURIFIER_DISABLED);
        $GLOBALS['Response']->redirect('/plugins/git/admin/?pane=mirrors_admin');
    }

    private function getGitSystemEventsQueueURL() {
        return "/admin/system_events/?queue=git";
    }

    private function updateAllowedProjectList($request) {
        $mirror_id             = $request->get('mirror_id');
        $mirror                = $this->git_mirror_mapper->fetch($mirror_id);
        $project_to_add        = $request->get('project-to-allow');
        $project_ids_to_remove = $request->get('project-ids-to-revoke');

        $this->checkSynchronizerToken('/plugins/git/admin/?pane=mirrors_admin&action=update-allowed-project-list&mirror_id=' . $mirror_id);

        if ($request->get('allow-project') && ! empty($project_to_add)) {
            $this->allowProjectOnMirror($mirror, $project_to_add);

        } elseif ($request->get('revoke-project') && ! empty($project_ids_to_remove)) {
            $this->revokeProjectsFromMirror($mirror, $project_ids_to_remove);
        }
    }

    private function allowProjectOnMirror(Git_Mirror_Mirror $mirror, $project_to_add) {
        $project = $this->project_manager->getProjectFromAutocompleter($project_to_add);

        if ($project && $this->git_mirror_resource_restrictor->allowProjectOnMirror($mirror, $project)) {
            $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('plugin_git', 'mirror_allowed_project_allow_project'));
            $GLOBALS['Response']->redirect('/plugins/git/admin/?pane=mirrors_admin&action=manage-allowed-projects&mirror_id=' . $mirror->id);
        }

        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_git', 'mirror_allowed_project_update_project_list_error'));
        $GLOBALS['Response']->redirect('/plugins/git/admin/?pane=mirrors_admin&action=manage-allowed-projects&mirror_id=' . $mirror->id);
    }

    private function revokeProjectsFromMirror(Git_Mirror_Mirror $mirror, $project_ids) {
        if (count($project_ids) > 0 &&
            $this->git_mirror_resource_restrictor->revokeProjectsFromMirror($mirror, $project_ids) &&
            $this->git_mirror_mapper->deleteFromDefaultMirrorsInProjects($mirror, $project_ids)
        ) {
            $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('plugin_git', 'mirror_allowed_project_revoke_projects'));
            $GLOBALS['Response']->redirect('/plugins/git/admin/?pane=mirrors_admin&action=manage-allowed-projects&mirror_id=' . $mirror->id);
        }

        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_git', 'mirror_allowed_project_update_project_list_error'));
        $GLOBALS['Response']->redirect('/plugins/git/admin/?pane=mirrors_admin&action=manage-allowed-projects&mirror_id=' . $mirror->id);
    }

    private function checkSynchronizerToken($url) {
        $token = new CSRFSynchronizerToken($url);
        $token->check();
    }

    private function createMirror(Codendi_Request $request) {
        $url      = $request->get('new_mirror_url');
        $hostname = $request->get('new_mirror_hostname');
        $ssh_key  = $request->get('new_mirror_key');
        $password = $request->get('new_mirror_pwd');
        $name     = $request->get('new_mirror_name');

        $this->csrf->check();

        try {
            $this->git_mirror_mapper->save($url, $hostname, $ssh_key, $password, $name);
        } catch (Git_Mirror_MissingDataException $e) {
            $this->redirectToCreateWithError($GLOBALS['Language']->getText('plugin_git','admin_mirror_fields_required'));
        } catch (Git_Mirror_CreateException $e) {
            $this->redirectToCreateWithError($GLOBALS['Language']->getText('plugin_git','admin_mirror_save_failed'));
        } catch (Git_Mirror_HostnameAlreadyUsedException $e) {
            $this->redirectToCreateWithError($GLOBALS['Language']->getText('plugin_git','admin_mirror_hostname_duplicate'));
        } catch (Git_Mirror_HostnameIsReservedException $e) {
            $this->redirectToCreateWithError($GLOBALS['Language']->getText('plugin_git','admin_mirror_hostname_reserved', array($hostname)));
        }
    }

    private function redirectToCreateWithError($message) {
        $GLOBALS['Response']->addFeedback('error', $message);
        $GLOBALS['Response']->redirect("?pane=mirrors_admin&action=show-add-mirror");
    }

    private function showAddMirror() {
        $title    = $GLOBALS['Language']->getText('plugin_git', 'descriptor_name');
        $renderer = TemplateRendererFactory::build()->getRenderer(dirname(GIT_BASE_DIR).'/templates');

        $admin_presenter = new Git_AdminMirrorAddPresenter(
            $title,
            $this->csrf
        );

        $GLOBALS['HTML']->header(array('title' => $title, 'selected_top_tab' => 'admin', 'main_classes' => array('framed-vertically')));
        $renderer->renderToPage('admin-plugin', $admin_presenter);
        $GLOBALS['HTML']->footer(array());
    }

    private function showEditMirror(Codendi_Request $request) {
        $title    = $GLOBALS['Language']->getText('plugin_git', 'descriptor_name');
        $renderer = TemplateRendererFactory::build()->getRenderer(dirname(GIT_BASE_DIR).'/templates');

        try {
            $mirror = $this->git_mirror_mapper->fetch($request->get('mirror_id'));
        } catch (Git_Mirror_MirrorNotFoundException $e) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_git','admin_mirror_cannot_update'));
            $GLOBALS['Response']->redirect('/plugins/git/admin/?pane=mirrors_admin');
        }

        $admin_presenter = new Git_AdminMirrorEditPresenter(
            $title,
            $this->csrf,
            $mirror
        );

        $GLOBALS['HTML']->header(array('title' => $title, 'selected_top_tab' => 'admin', 'main_classes' => array('framed-vertically')));
        $renderer->renderToPage('admin-plugin', $admin_presenter);
        $GLOBALS['HTML']->footer(array());
    }

    private function modifyMirror(Codendi_Request $request) {
        try {
            $this->csrf->check();

            $mirror_id = $request->get('mirror_id');
            $update    = $this->git_mirror_mapper->update(
                $mirror_id,
                $request->get('mirror_url'),
                $request->get('mirror_hostname'),
                $request->get('mirror_key'),
                $request->get('mirror_name')
            );

            if (! $update) {
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_git','admin_mirror_cannot_update'));
            } else  {
                $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('plugin_git','admin_mirror_updated'));
            }
        } catch (Git_Mirror_MirrorNotFoundException $e) {
            $this->redirectToEditFormWithError($mirror_id, $GLOBALS['Language']->getText('plugin_git','admin_mirror_cannot_update'));
        } catch (Git_Mirror_MirrorNoChangesException $e) {
            $this->redirectToEditFormWithError($mirror_id, $GLOBALS['Language']->getText('plugin_git','admin_mirror_no_changes'));
        } catch (Git_Mirror_MissingDataException $e) {
            $this->redirectToEditFormWithError($mirror_id, $GLOBALS['Language']->getText('plugin_git','admin_mirror_fields_required'));
        } catch (Git_Mirror_HostnameAlreadyUsedException $e) {
            $this->redirectToEditFormWithError($mirror_id, $GLOBALS['Language']->getText('plugin_git','admin_mirror_hostname_duplicate'));
        } catch (Git_Mirror_HostnameIsReservedException $e) {
            $this->redirectToEditFormWithError($mirror_id, $GLOBALS['Language']->getText('plugin_git','admin_mirror_hostname_reserved', array($request->get('mirror_hostname'))));
        }
    }

    private function redirectToEditFormWithError($mirror_id, $message) {
        $GLOBALS['Response']->addFeedback('error', $message);
        $GLOBALS['Response']->redirect("?pane=mirrors_admin&action=show-edit-mirror&mirror_id=".$mirror_id);
    }

    private function deleteMirror(Codendi_Request $request) {
        try {
            $this->csrf->check();

            $id     = $request->get('mirror_id');
            $delete = $this->git_mirror_mapper->delete($id);

            if (! $delete) {
                $GLOBALS['Response']->addFeedback(
                    Feedback::ERROR,
                    $GLOBALS['Language']->getText('plugin_git','admin_mirror_cannot_delete')
                );

                return;
            }

            if (! $this->git_mirror_mapper->deleteFromDefaultMirrors($id)) {
                $GLOBALS['Response']->addFeedback(
                    Feedback::ERROR,
                    $GLOBALS['Language']->getText('plugin_git','admin_mirror_defalut_cannot_delete')
                );
            }

        } catch (Git_Mirror_MirrorNotFoundException $e) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_git','admin_mirror_cannot_delete'));
        }

        $GLOBALS['Response']->redirect('/plugins/git/admin/?pane=mirrors_admin');
    }
}
