(function () {
    angular
        .module('campaign')
        .controller('CampaignNewCtrl', CampaignNewCtrl);

    CampaignNewCtrl.$inject = [
        '$scope',
        '$state',
        'gettextCatalog',
        'CampaignService',
        'EnvironmentService',
        'DefinitionService',
        'SharedPropertiesService'
    ];

    function CampaignNewCtrl(
        $scope,
        $state,
        gettextCatalog,
        CampaignService,
        EnvironmentService,
        DefinitionService,
        SharedPropertiesService
    ) {
        var project_id = SharedPropertiesService.getProjectId();

        _.extend($scope, {
            loading_environments: true,
            loading_definitions:  true,
            breadcrumb_label:     gettextCatalog.getString('Campaign creation'),
            definitions:          [],
            createCampaign:       createCampaign,
            campaign: {
                label:        '',
                environments: []
            }
        });

        getEnvironments(project_id, 50, 0);
        getDefinitions(project_id, 50, 0);

        function createCampaign(campaign) {
            var environments = extractChoosenDefinitionsByEnvironment(campaign);

            CampaignService
                .createCampaign({
                  project_id:   project_id,
                  label:        campaign.label,
                  environments: environments
                })
                .then(function () {
                    $state.go('campaigns.list', {}, {reload: true});
            });
        }

        function extractChoosenDefinitionsByEnvironment(campaign) {
            var environments = {};

            campaign.environments.forEach(function (environment) {
                var definition_ids = _(environment.definitions)
                    .omit(shouldValueBeOmitted)
                    .keys()
                    .value();

                if (definition_ids.length > 0) {
                    environments[environment.id] = definition_ids;
                }
            });

            return environments;
        }

        function shouldValueBeOmitted(value) {
            return ! value;
        }

        function getEnvironments(project_id, limit, offset) {
            EnvironmentService.getEnvironments(project_id, limit, offset).then(function(data) {
                data.results.forEach(addPossibleEnvironmentInCampaign);

                if ($scope.campaign.environments.length < data.total) {
                    getEnvironments(project_id, limit, offset + limit);
                } else {
                    $scope.loading_environments = false;
                }
            });
        }

        function addPossibleEnvironmentInCampaign(environment) {
            $scope.campaign.environments.push({
                label:      environment.label,
                id:         environment.id,
                is_choosen: false,
                definitions: {}
            });
        }

        function getDefinitions(project_id, limit, offset) {
            DefinitionService.getDefinitions(project_id, limit, offset).then(function(data) {
                $scope.definitions = $scope.definitions.concat(data.results);

                if ($scope.definitions.length < data.total) {
                    getDefinitions(project_id, limit, offset + limit);
                } else {
                    $scope.loading_definitions = false;
                }
            });
        }
    }
})();