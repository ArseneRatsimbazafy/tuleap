import _ from 'lodash';
import angular from 'angular';

import { sortAlphabetically } from '../ksort.js';

export default ExecutionListCtrl;

ExecutionListCtrl.$inject = [
    '$scope',
    '$state',
    '$filter',
    'ExecutionService',
    'CampaignService',
    'SocketService',
    'SharedPropertiesService',
    'ExecutionRestService'
];

function ExecutionListCtrl(
    $scope,
    $state,
    $filter,
    ExecutionService,
    CampaignService,
    SocketService,
    SharedPropertiesService,
    ExecutionRestService
) {
    Object.assign($scope, {
        checkActiveClassOnExecution,
        toggleStatus,
        viewTestExecution,
        canCategoryBeDisplayed,
        hideDetailsForRemovedTestExecution,
    });

    this.$onInit = initialization;

    function checkActiveClassOnExecution(execution) {
        return $state.includes('campaigns.executions.detail', { execid: execution.id, defid: execution.definition.id });
    }

    function toggleStatus(executionStatus) {
        $scope.status[executionStatus] = !$scope.status[executionStatus];
    }

    function viewTestExecution(current_execution) {
        var old_execution,
            old_execution_id = '';

        if (_.has(ExecutionService.executions, $scope.execution_id)) {
            old_execution = ExecutionService.executions[$scope.execution_id];
        }

        if (! _.isEmpty(old_execution)) {
            if (current_execution.id !== old_execution.id) {
                old_execution_id = old_execution.id;
                updateViewTestExecution(current_execution.id, old_execution_id);
            }
        } else {
            updateViewTestExecution(current_execution.id, old_execution_id);
        }
    }

    $scope.$on('$destroy', function() {
        var toolbar = angular.element('.toolbar');
        if (toolbar) {
            toolbar.removeClass('hide-toolbar');
        }

        if ($scope.execution_id) {
            ExecutionRestService.leaveTestExecution($scope.execution_id);
            ExecutionService.removeViewTestExecution($scope.execution_id, SharedPropertiesService.getCurrentUser());
        }

        ExecutionService.removeAllPresencesOnCampaign();
    });

    $scope.$on('execution-detail-destroy', function() {
        $scope.execution_id = '';
    });

    $scope.$on('controller-reload', function() {
        initialization();
    });

    SocketService.listenNodeJSServer().then(function() {
        SocketService.listenToUserScore();
        SocketService.listenTokenExpired();
        SocketService.listenToExecutionViewed();
        SocketService.listenToExecutionCreated();
        SocketService.listenToExecutionUpdated();
        SocketService.listenToExecutionDeleted(function(execution) {
            hideDetailsForRemovedTestExecution();
        });
        SocketService.listenToExecutionLeft();
        SocketService.listenToCampaignUpdated(function(campaign) {
            $scope.campaign = campaign;
            ExecutionService.updateCampaign(campaign);
        });
    }, () => {
        // ignore the fact that there is no nodejs server
    });


    function initialization() {
        var toolbar = angular.element('.toolbar');
        if (toolbar) {
            toolbar.addClass('hide-toolbar');
        }

        $scope.campaign_id = $state.params.id;
        $scope.execution_id = $state.params.execid;

        SharedPropertiesService.setCampaignId($scope.campaign_id);

        loadExecutions();
        CampaignService.getCampaign($scope.campaign_id).then((campaign) => {
            $scope.campaign             = campaign;
            $scope.search               = '';
            $scope.loading              = loading;
            $scope.status               = {
                passed:  false,
                failed:  false,
                blocked: false,
                notrun:  false
            };

            ExecutionService.updateCampaign($scope.campaign);
        });
    }

    function loadExecutions() {
        return ExecutionService.loadExecutions($scope.campaign_id).then(function() {
            ExecutionService.removeAllViewTestExecution();
            if ($scope.execution_id) {
                updateViewTestExecution($scope.execution_id, '');
            }

            ExecutionService.executions_loaded = true;
            ExecutionService.displayPresencesForAllExecutions();

            $scope.categories = sortAlphabetically(ExecutionService.executions_by_categories_by_campaigns[$scope.campaign_id]);
        });
    }

    function updateViewTestExecution(current_execution_id, old_execution_id) {
        ExecutionService.addPresenceCampaign(SharedPropertiesService.getCurrentUser());

        ExecutionRestService.changePresenceOnTestExecution(current_execution_id, old_execution_id).then(function() {
            ExecutionService.removeViewTestExecution(old_execution_id, SharedPropertiesService.getCurrentUser());
            ExecutionService.viewTestExecution(current_execution_id, SharedPropertiesService.getCurrentUser());
            $scope.execution_id = current_execution_id;
        });
    }

    function hideDetailsForRemovedTestExecution() {
        if ($state.includes('campaigns.executions.detail')) {
            var campaign_executions = ExecutionService.executionsForCampaign($scope.campaign_id),
                current_execution_exists = _.any(campaign_executions, checkActiveClassOnExecution);

            if (! current_execution_exists) {
                $state.go('^');
            }
        }
    }

    function loading() {
        return ExecutionService.loading[$scope.campaign_id] === true;
    }

    function canCategoryBeDisplayed(category) {
        var filtered_executions = $filter('ExecutionListFilter')(
            category.executions,
            $scope.search,
            $scope.status
        );

        return _.size(filtered_executions) > 0;
    }
}
