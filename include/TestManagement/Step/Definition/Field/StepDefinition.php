<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

namespace Tuleap\TestManagement\Step\Definition\Field;

use Codendi_HTMLPurifier;
use DataAccessObject;
use PFUser;
use ReferenceManager;
use SimpleXMLElement;
use TemplateRendererFactory;
use Tracker_Artifact;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_ChangesetValue;
use Tracker_Artifact_ChangesetValue_Text;
use Tracker_FormElement_Field;
use Tracker_FormElement_FieldVisitor;
use Tracker_Report_Criteria;
use Tuleap\TestManagement\Step\Step;
use Tuleap\TestManagement\Step\StepPresenter;
use Tuleap\Tracker\FormElement\Field\File\CreatedFileURLMapping;
use Tuleap\Tracker\FormElement\TrackerFormElementExternalField;
use UserXMLExporter;

class StepDefinition extends Tracker_FormElement_Field implements TrackerFormElementExternalField
{
    public const START_RANK = 1;
    public const TYPE       = 'ttmstepdef';

    public function accept(Tracker_FormElement_FieldVisitor $visitor)
    {
        $visitor->visitExternalField($this);
    }

    /**
     * @return string the label of the formElement (mainly used in admin part)
     */
    public static function getFactoryLabel()
    {
        return dgettext('tuleap-testmanagement', 'Step definition');
    }

    /**
     * @return string the description of the formElement (mainly used in admin part)
     */
    public static function getFactoryDescription()
    {
        return dgettext('tuleap-testmanagement', 'Definition of a step');
    }

    /**
     * @return string the path to the icon to use an element
     */
    public static function getFactoryIconUseIt()
    {
        return TESTMANAGEMENT_BASE_URL . '/images/ic/tick-white.png';
    }

    /**
     * @return string the path to the icon to create an element
     */
    public static function getFactoryIconCreate()
    {
        return TESTMANAGEMENT_BASE_URL . '/images/ic/tick-white--plus.png';
    }

    public static function getFactoryUniqueField()
    {
        return true;
    }

    /**
     * Display the html f in the admin ui
     *
     * @return string html
     */
    protected function fetchAdminFormElement()
    {
        return '<textarea></textarea>';
    }

    public function getRESTAvailableValues()
    {
        return null;
    }

    public function canBeUsedAsReportCriterion()
    {
        return false;
    }

    public function canBeUsedAsReportColumn()
    {
        return false;
    }

    /**
     * Display the field value as a criteria
     *
     * @param Tracker_ReportCriteria $criteria
     *
     * @return string
     * @see fetchCriteria
     */
    public function fetchCriteriaValue($criteria)
    {
        return '';
    }

    /**
     * Fetch the value
     *
     * @param mixed $value the value of the field
     *
     * @return string
     */
    public function fetchRawValue($value)
    {
        return '';
    }

    /**
     * Get the "from" statement to allow search with this field
     * You can join on 'c' which is a pseudo table used to retrieve
     * the last changeset of all artifacts.
     *
     * @param Tracker_Report_Criteria $criteria
     *
     * @return string
     */
    public function getCriteriaFrom($criteria)
    {
        return '';
    }

    /**
     * Get the "where" statement to allow search with this field
     *
     * @param Tracker_ReportCriteria $criteria
     *
     * @return string
     * @see getCriteriaFrom
     */
    public function getCriteriaWhere($criteria)
    {
        return '';
    }

    public function getQueryFrom()
    {
        return '';
    }

    public function getQuerySelect()
    {
        return '';
    }

    /**
     * Return the dao of the criteria value used with this field.
     * @return DataAccessObject
     */
    protected function getCriteriaDao()
    {
        return null;
    }

    /**
     * Fetch the html code to display the field value in artifact
     *
     * @param Tracker_Artifact                $artifact         The artifact
     * @param Tracker_Artifact_ChangesetValue $value            The actual value of the field
     * @param array                           $submitted_values The value already submitted by the user
     *
     * @return string
     */
    protected function fetchArtifactValue(
        Tracker_Artifact $artifact,
        ?Tracker_Artifact_ChangesetValue $value = null,
        $submitted_values = []
    ) {
        $steps = $this->getStepsPresentersFromSubmittedValues($submitted_values);
        if (empty($steps)) {
            $steps = $this->getStepsPresentersFromChangesetValue($value);
        }

        return $this->renderStepEditionToString($steps);
    }

    private function getDefaultFormat(PFUser $user)
    {
        $user_preference = $user->getPreference(PFUser::EDITION_DEFAULT_FORMAT);

        if (! $user_preference || $user_preference === Tracker_Artifact_ChangesetValue_Text::TEXT_CONTENT) {
            return Tracker_Artifact_ChangesetValue_Text::TEXT_CONTENT;
        }

        return Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT;
    }

    public function fetchArtifactValueWithEditionFormIfEditable(
        Tracker_Artifact $artifact,
        ?Tracker_Artifact_ChangesetValue $value,
        array $submitted_values
    ) {
        return $this->fetchArtifactValueReadOnly($artifact, $value) .
            $this->getHiddenArtifactValueForEdition($artifact, $value, $submitted_values);
    }

    /**
     * Fetch the html code to display the field value in artifact in read only
     *
     * @param Tracker_Artifact                $artifact The artifact
     * @param Tracker_Artifact_ChangesetValue $value    The actual value of the field
     *
     * @return string
     */
    public function fetchArtifactValueReadOnly(
        Tracker_Artifact $artifact,
        ?Tracker_Artifact_ChangesetValue $value = null
    ) {
        $renderer = TemplateRendererFactory::build()->getRenderer(TESTMANAGEMENT_BASE_DIR . '/templates');

        $purifier       = Codendi_HTMLPurifier::instance();
        $no_value_label = $this->getNoValueLabel();

        return $renderer->renderToString(
            'step-def-readonly',
            [
                'steps'                   => $this->getStepsPresentersFromChangesetValue($value),
                'purified_no_value_label' => $purifier->purify($no_value_label, CODENDI_PURIFIER_FULL)
            ]
        );
    }

    /**
     * Fetch the html code to display the field value in new artifact submission form
     *
     * @return string html
     */
    protected function fetchSubmitValue(array $submitted_values)
    {
        $submitted_values = $submitted_values ?: [];

        $steps = $this->getStepsPresentersFromSubmittedValues($submitted_values);

        return $this->renderStepEditionToString($steps);
    }

    /**
     * Fetch the html code to display the field value in masschange submission form
     *
     * @return string html
     */
    protected function fetchSubmitValueMasschange()
    {
    }

    /**
     * Fetch the html code to display the field value in tooltip
     *
     * @param Tracker_Artifact                $artifact
     * @param Tracker_Artifact_ChangesetValue $value The changeset value of the field
     *
     * @return string
     */
    protected function fetchTooltipValue(Tracker_Artifact $artifact, ?Tracker_Artifact_ChangesetValue $value = null)
    {
        return '';
    }

    public function fetchAddTooltip($used, $prefix = '')
    {
        return '';
    }

    protected function getValueDao()
    {
        return new StepDefinitionChangesetValueDao();
    }

    /**
     * Fetch the value to display changes in followups
     *
     * @param Tracker_ $artifact
     * @param array    $from the value(s) *before*
     * @param array    $to   the value(s) *after*
     *
     * @return string
     */
    public function fetchFollowUp($artifact, $from, $to)
    {
        return '';
    }

    /**
     * Fetch the value in a specific changeset
     *
     * @param Tracker_Artifact_Changeset $changeset
     *
     * @return string
     */
    public function fetchRawValueFromChangeset($changeset)
    {
        return '';
    }

    /**
     * Validate a value
     *
     * @param Tracker_Artifact $artifact The artifact
     * @param mixed            $value    data coming from the request. May be string or array.
     *
     * @return bool true if the value is considered ok
     */
    protected function validate(Tracker_Artifact $artifact, $value)
    {
        if ($this->doesUserWantToRemoveAllSteps($value)) {
            return true;
        }

        $rule = new \Rule_String();
        foreach ($value['description'] as $key => $submitted_step_description) {
            if (! isset($value['expected_results'][$key])) {
                return false;
            }

            if (! $rule->isValid($submitted_step_description) || ! $rule->isValid($value['expected_results'][$key])) {
                $GLOBALS['Response']->addFeedback(
                    \Feedback::ERROR,
                    $GLOBALS['Language']->getText(
                        'plugin_tracker_common_artifact',
                        'error_text_value',
                        $this->getLabel()
                    )
                );

                return false;
            }

            if (! $this->isSubmittedFormatValid($value, 'description_format', $key)) {
                return false;
            }

            if (! $this->isSubmittedFormatValid($value, 'expected_results_format', $key)) {
                return false;
            }
        }

        return true;
    }

    private function isSubmittedFormatValid($value, $format_key, $key)
    {
        return isset($value[$format_key][$key])
            && in_array(
                $value[$format_key][$key],
                [Tracker_Artifact_ChangesetValue_Text::TEXT_CONTENT, Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT]
            );
    }

    private function doesUserWantToRemoveAllSteps(array $value)
    {
        return isset($value['no_steps']) && $value['no_steps'];
    }

    /**
     * @see Tracker_FormElement_Field::hasChanges()
     */
    public function hasChanges(
        Tracker_Artifact $artifact,
        Tracker_Artifact_ChangesetValue $previous_changesetvalue,
        $new_value
    ) {
        $existing_steps = $previous_changesetvalue->getValue();
        if ($this->doesUserWantToRemoveAllSteps($new_value)) {
            return ! empty($existing_steps);
        }

        $submitted_steps = [];
        $rank            = self::START_RANK;
        foreach ($new_value['description'] as $key => $submitted_step_description) {
            $submitted_description_format = Tracker_Artifact_ChangesetValue_Text::TEXT_CONTENT;
            if (isset($new_value['description_format'][$key])) {
                $submitted_description_format = $new_value['description_format'][$key];
            }
            $submitted_expected_results = '';
            if (isset($new_value['expected_results'][$key])) {
                $submitted_expected_results = $new_value['expected_results'][$key];
            }
            $submitted_expected_results_format = Tracker_Artifact_ChangesetValue_Text::TEXT_CONTENT;
            if (isset($new_value['expected_results_format'][$key])) {
                $submitted_expected_results_format = $new_value['expected_results_format'][$key];
            }
            $submitted_steps[] = new Step(
                $new_value['id'][$key],
                $submitted_step_description,
                $submitted_description_format,
                $submitted_expected_results,
                $submitted_expected_results_format,
                $rank
            );
            $rank++;
        }

        return array_diff($submitted_steps, $existing_steps) !== [] ||
            array_diff($existing_steps, $submitted_steps) !== [];
    }

    protected function saveValue(
        $artifact,
        $changeset_value_id,
        $value,
        ?Tracker_Artifact_ChangesetValue $previous_changesetvalue,
        CreatedFileURLMapping $id_mapping
    ) {
        $steps = $this->transformSubmittedValuesIntoArrayOfStructuredSteps($value);

        return $this->getValueDao()->create($changeset_value_id, $steps) &&
            $this->extractCrossRefs($artifact, $value);
    }

    private function transformSubmittedValuesIntoArrayOfStructuredSteps(array $submitted_values)
    {
        if ($this->doesUserWantToRemoveAllSteps($submitted_values)) {
            return [];
        }

        $steps = [];
        $rank  = StepDefinition::START_RANK;
        foreach ($submitted_values['description'] as $key => $description) {
            $description = trim($description);
            if (! $description) {
                continue;
            }
            if (! isset($submitted_values['description_format'][$key])) {
                continue;
            }
            $description_format = $submitted_values['description_format'][$key];

            $expected_results = '';
            if (isset($submitted_values['expected_results'][$key])) {
                $expected_results = trim($submitted_values['expected_results'][$key]);
            }
            $expected_results_format = '';
            if (isset($submitted_values['expected_results_format'][$key])) {
                $expected_results_format = $submitted_values['expected_results_format'][$key];
            }

            $steps[] = new Step(
                0,
                $description,
                $description_format,
                $expected_results,
                $expected_results_format,
                $rank
            );
        }

        return $steps;
    }

    private function extractCrossRefs(Tracker_Artifact $artifact, array $submitted_steps)
    {
        if (! isset($submitted_steps['description']) && ! isset($submitted_steps['expected_results'])) {
            return true;
        }

        $concatenated_descriptions     = implode(PHP_EOL, $submitted_steps['description']);
        $concatenated_expected_results = implode(PHP_EOL, $submitted_steps['expected_results']);

        $text = $concatenated_descriptions . PHP_EOL . $concatenated_expected_results;

        return ReferenceManager::instance()->extractCrossRef(
            $text,
            $artifact->getId(),
            Tracker_Artifact::REFERENCE_NATURE,
            $this->getTracker()->getGroupID(),
            $this->getCurrentUser()->getId(),
            $this->getTracker()->getItemName()
        );
    }

    /**
     * Get the value of this field
     *
     * @param Tracker_Artifact_Changeset $changeset   The changeset (needed in only few cases like 'lud' field)
     * @param int                        $value_id    The id of the value
     * @param bool $has_changed If the changeset value has changed from the rpevious one
     *
     * @return Tracker_Artifact_ChangesetValue or null if not found
     */
    public function getChangesetValue($changeset, $value_id, $has_changed)
    {
        $steps = [];
        $rank  = self::START_RANK;
        foreach ($this->getValueDao()->searchById($value_id) as $row) {
            $steps[] = new Step(
                $row['id'],
                $row['description'],
                $row['description_format'],
                $row['expected_results'],
                $row['expected_results_format'],
                $rank
            );
            $rank++;
        }

        return new StepDefinitionChangesetValue($value_id, $changeset, $this, $has_changed, $steps);
    }

    /**
     * Display the field as a Changeset value.
     * Used in report table
     *
     * @param int   $artifact_id  the corresponding artifact id
     * @param int   $changeset_id the corresponding changeset
     * @param mixed $value        the value of the field
     * @param int   $report_id    the id of the calling report
     *
     * @return string
     */
    public function fetchChangesetValue($artifact_id, $changeset_id, $value, $report_id = null, $from_aid = null)
    {
        return '';
    }

    public function getFormAdminVisitor(Tracker_FormElement_Field $element, array $used_element)
    {
        return new ViewAdmin($element, $used_element);
    }

    /**
     * @param Tracker_Artifact_ChangesetValue|null $value
     *
     * @return StepPresenter[]
     */
    private function getStepsPresentersFromChangesetValue(?Tracker_Artifact_ChangesetValue $value = null)
    {
        $steps = [];
        if ($value) {
            $steps = $value->getValue();
        }

        return array_map(
            function (Step $step) {
                return $this->getStepPresenter($step);
            },
            $steps
        );
    }

    private function getStepPresenter(Step $step)
    {
        return new StepPresenter($step, $this->getTracker()->getProject());
    }

    /**
     * @return StepPresenter
     */
    private function getEmptyStepPresenter()
    {
        $default_format = $this->getDefaultFormat($this->getCurrentUser());
        $empty_step     = new Step(0, '', $default_format, '', $default_format, 0);

        return $this->getStepPresenter($empty_step);
    }

    /**
     * @param StepPresenter[] $steps_presenters
     *
     * @return String
     */
    protected function renderStepEditionToString(array $steps_presenters)
    {
        $renderer = TemplateRendererFactory::build()->getRenderer(TESTMANAGEMENT_BASE_DIR . '/templates');

        $empty_step_presenter = $this->getEmptyStepPresenter();

        return $renderer->renderToString(
            'step-def-edit',
            [
                'field_id'                => $this->id,
                'json_encoded_steps'      => json_encode($steps_presenters),
                'json_encoded_empty_step' => json_encode($empty_step_presenter)
            ]
        );
    }

    /**
     * @param array $submitted_values
     *
     * @return StepPresenter[]
     */
    private function getStepsPresentersFromSubmittedValues(array $submitted_values)
    {
        $steps = [];

        $submitted_steps = $this->getValueFromSubmitOrDefault($submitted_values);
        if ($submitted_steps) {
            $steps = array_map(
                function (Step $step) {
                    return $this->getStepPresenter($step);
                },
                $this->transformSubmittedValuesIntoArrayOfStructuredSteps($submitted_steps)
            );
        }

        return $steps;
    }
}
