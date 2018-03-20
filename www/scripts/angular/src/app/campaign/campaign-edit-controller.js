import _ from 'lodash';

import { sortAlphabetically } from '../ksort.js';

export default CampaignEditCtrl;

CampaignEditCtrl.$inject = [
    'modal_instance',
    '$scope',
    '$q',
    '$state',
    'SharedPropertiesService',
    'CampaignService',
    'DefinitionService',
    'ExecutionService',
    'NewTuleapArtifactModalService',
    'editCampaignCallback'
];

function CampaignEditCtrl(
    modal_instance,
    $scope,
    $q,
    $state,
    SharedPropertiesService,
    CampaignService,
    DefinitionService,
    ExecutionService,
    NewTuleapArtifactModalService,
    editCampaignCallback
) {
    var project_id,
        campaign_id;

    _.extend($scope, {
        tests_list            : {},
        test_reports          : [],
        filters               : {},
        selectReportTests     : selectReportTests,
        showAddTestModal      : showAddTestModal,
        toggleCategory        : toggleCategory,
        toggleTest            : toggleTest,
        addedTests            : addedTests,
        removedTests          : removedTests,
        editCampaign          : editCampaign,
        categoryCheckmark     : categoryCheckmark,
        testCheckmark         : testCheckmark,
        diffState             : diffState,
    });

    this.$onInit = function() {
        project_id  = SharedPropertiesService.getProjectId();
        campaign_id = $state.params.id;

        SharedPropertiesService.setCampaignId(campaign_id);

        CampaignService.getCampaign(campaign_id).then((campaign) => {
            $scope.campaign       = campaign;
            $scope.filters.search = '';

            loadTestReports();

            $q.all([
                loadDefinitions(),
                loadExecutions()
            ]).then(function(results) {
                var definitions = results[0],
                    executions = results[1];
                $scope.tests_list = buildInitialTestsList(definitions, executions);
            });
        });
    };

    function loadTestReports() {
        DefinitionService.getDefinitionReports().then(function(reports) {
            // data: [{id: <int>, label: <string>}]
            $scope.test_reports = reports;
        });
    }

    function loadDefinitions(options) {
        var options     = options || {},
            limit       = options.limit || 100,
            offset      = options.offset || 0,
            report_id   = options.report_id,
            definitions = options.definitions || [];

        return DefinitionService.getDefinitions(project_id, limit, offset, report_id).then(function(data) {
            definitions = definitions.concat(data.results);

            if (definitions.length === data.total) {
                return definitions;
            }

            return loadDefinitions({
                limit: limit,
                offset: offset + limit,
                report_id: report_id,
                definitions: definitions
            });
        });
    }

    function loadExecutions() {
        return ExecutionService.loadExecutions(campaign_id).then(function() {
            return ExecutionService.executionsForCampaign(campaign_id);
        });
    }

    function buildInitialTestsList(definitions, executions) {
        let tests_list = {};

        _.forEach(definitions, function(definition) {
            var category = definition.category;

            if (! _.has(tests_list, category)) {
                tests_list[category] = buildCategory(category);
            }

            tests_list[category].tests[definition.id] = buildTest(definition, null, false);
        });

        _.forEach(executions, function(execution) {
            var definition = execution.definition;
            var category = definition.category || DefinitionService.UNCATEGORIZED;

            _.merge(tests_list[category].tests[definition.id], {
                execution: execution,
                selected: true
            });
        });

        return sortAlphabetically(tests_list);
    }

    function buildCategory(category) {
        return {
            tests: {},
            label: category
        };
    }

    function buildTest(definition, execution, selected) {
        return {
            definition: definition,
            execution: execution,
            selected: selected
        };
    }

    function selectedTests(category) {
        return _.filter(category.tests, function(test) {
            return test.selected;
        });
    }

    function toggleCategory(category) {
        if (selectedTests(category).length === _.size(category.tests)) {
            _.forEach(category.tests, function(test) { test.selected = false; });
        } else {
            _.forEach(category.tests, function(test) { test.selected = true; });
        }
    }

    function toggleTest(test) {
        test.selected = !test.selected;
    }

    function categoryCheckmark(category) {
        switch (selectedTests(category).length) {
          case 0:
              return 'fa-square-o';
          case _.size(category.tests):
              return 'fa-check-square-o';
          default:
              return 'fa-minus-square-o';
        }
    }

    function testCheckmark(test) {
        return test.selected ? 'fa-check-square-o' : 'fa-square-o';
    }

    function diffState(test) {
        if (test.execution !== null && test.selected) {
            return 'selected';
        } else if (test.execution !== null) {
            return 'removed';
        } else if (test.selected) {
            return 'added';
        } else {
            return 'unselected';
        }
    }

    function addedTests() {
        return _($scope.tests_list)
            .map(function(category) {
                return _.select(category.tests, { execution: null, selected: true });
            })
            .flatten()
            .value();
    }

    function removedTests() {
        return _($scope.tests_list)
            .map(function(category) {
                return _(category.tests)
                    .reject({ execution: null })
                    .reject({ selected: true })
                    .value();
            })
            .flatten()
            .value();
    }

    function selectReportTests() {
        var selected_report = $scope.filters.selected_report;

        if (_.isEmpty(selected_report)) {
            return;
        }

        loadDefinitions({ report_id: selected_report })
            .then(function(definitions) {
                _.forEach($scope.tests_list, function(category) {
                    _.forEach(category.tests, function(test) {
                        test.selected = _.some(definitions, { id: test.definition.id });
                    });
                });
            });
    }

    function showAddTestModal() {
        var callback = function(definition_id) {
            DefinitionService.getDefinitionById(definition_id).then(addTest);
        };

        NewTuleapArtifactModalService.showCreation(SharedPropertiesService.getDefinitionTrackerId(), null, callback);
    }

    function addTest(definition) {
        var category = definition.category || DefinitionService.UNCATEGORIZED;

        if (! _.has($scope.tests_list, category)) {
            $scope.tests_list[category] = buildCategory(category);
        }

        $scope.tests_list[category].tests[definition.id] = buildTest(definition, null, true);
    }

    function editCampaign(campaign) {
        $scope.submitting_changes = true;

        const definition_ids = _.map(addedTests(), (test) => { return test.definition.id; });
        const execution_ids  = _.map(removedTests(), (test) => { return test.execution.id; });

        CampaignService.patchExecutions(campaign.id, definition_ids, execution_ids).then(() => {
            return CampaignService.patchCampaign(
                campaign.id,
                campaign.label,
                campaign.job_configuration
            );
        }).then(response => {
            $scope.submitting_changes = false;

            if (editCampaignCallback) {
                editCampaignCallback(response);
            }

            modal_instance.tlp_modal.hide();
        });
    }
}

