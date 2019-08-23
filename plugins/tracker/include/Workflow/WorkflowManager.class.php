<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright (c) Enalean, 2016-2019. All Rights Reserved.
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

class WorkflowManager {
    protected $tracker;

    public function __construct($tracker)
    {
        $this->tracker = $tracker;
    }

    public function process(TrackerManager $engine, HTTPRequest $request, PFUser $current_user)
    {
        $workflow_factory = WorkflowFactory::instance();
        if ($request->get('func') == Workflow::FUNC_ADMIN_RULES) {
            $token = new CSRFSynchronizerToken(TRACKER_BASE_URL. '/?'. http_build_query(
                array(
                    'tracker' => (int)$this->tracker->id,
                    'func'    => Workflow::FUNC_ADMIN_RULES,
                    )
            ));
            $rule_date_factory = new Tracker_Rule_Date_Factory(new Tracker_Rule_Date_Dao(), Tracker_FormElementFactory::instance());
            $action = new Tracker_Workflow_Action_Rules_EditRules($this->tracker, $rule_date_factory, $token);
        } elseif ($request->get('func') == Workflow::FUNC_ADMIN_CROSS_TRACKER_TRIGGERS) {
            $token = new CSRFSynchronizerToken(TRACKER_BASE_URL. '/?'. http_build_query(
                array(
                    'tracker' => (int)$this->tracker->id,
                    'func'    => Workflow::FUNC_ADMIN_CROSS_TRACKER_TRIGGERS,
                    )
            ));

            $renderer = TemplateRendererFactory::build()->getRenderer(TRACKER_BASE_DIR.'/../templates');
            $action   = new Tracker_Workflow_Action_Triggers_EditTriggers(
                $this->tracker,
                $token,
                $renderer,
                $workflow_factory->getTriggerRulesManager()
            );
        } else if ($request->get('func') == Workflow::FUNC_ADMIN_GET_TRIGGERS_RULES_BUILDER_DATA) {
            $action = new Tracker_Workflow_Action_Triggers_GetTriggersRulesBuilderData($this->tracker, Tracker_FormElementFactory::instance());
        } else if ($request->get('func') == Workflow::FUNC_ADMIN_ADD_TRIGGER) {
            $action = new Tracker_Workflow_Action_Triggers_AddTrigger($this->tracker, Tracker_FormElementFactory::instance(), $workflow_factory->getTriggerRulesManager());
        } else if ($request->get('func') == Workflow::FUNC_ADMIN_DELETE_TRIGGER) {
            $action = new Tracker_Workflow_Action_Triggers_DeleteTrigger($this->tracker, $workflow_factory->getTriggerRulesManager());
        } else if ($request->get('create')) {
            $action = new Tracker_Workflow_Action_Transitions_Create($this->tracker, $workflow_factory);
        } else if ($request->get('edit_transition')) {
            $action = new Tracker_Workflow_Action_Transitions_EditTransition($this->tracker, TransitionFactory::instance(), new Transition_PostActionFactory());
        } else if ($request->get('delete')) {
            $action = new Tracker_Workflow_Action_Transitions_Delete($this->tracker, $workflow_factory);
        } else if ($request->get('transitions')) {
            $action = new Tracker_Workflow_Action_Transitions_CreateMatrix($this->tracker, $workflow_factory, Tracker_FormElementFactory::instance());
        } else if ($request->get('workflow_details') && $request->isPost()) {
            $action     = new Tracker_Workflow_Action_Transitions_Details($this->tracker, TransitionFactory::instance());
        } else {
            $action = new Tracker_Workflow_Action_Transitions_DefineWorkflow($this->tracker, WorkflowFactory::instance(), Tracker_FormElementFactory::instance());
        }
        $action->process($engine, $request, $current_user);
    }
}
