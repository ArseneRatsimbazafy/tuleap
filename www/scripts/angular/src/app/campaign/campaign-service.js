export default CampaignService;

CampaignService.$inject = [
    'Restangular',
    'SharedPropertiesService'
];

function CampaignService(
    Restangular,
    SharedPropertiesService
) {
    var rest = Restangular.withConfig(function(RestangularConfigurer) {
        RestangularConfigurer.setFullResponse(true);
        RestangularConfigurer.setBaseUrl('/api/v1');
    });

    return {
        getCampaign     : getCampaign,
        getCampaigns    : getCampaigns,
        createCampaign  : createCampaign,
        patchCampaign   : patchCampaign,
        patchExecutions : patchExecutions,
    };

    function getCampaign(campaign_id) {
        return rest.one('testmanagement_campaigns', campaign_id).get().$object;
    }

    function getCampaigns(project_id, milestone_id, campaign_status, limit, offset) {
        return rest.one('projects', project_id)
            .all('testmanagement_campaigns')
            .getList({
                limit: limit,
                offset: offset,
                query : {
                    status: campaign_status,
                    milestone_id: milestone_id
                }
            })
            .then(function(response) {
                var result = {
                    results: response.data,
                    total: response.headers('X-PAGINATION-SIZE')
                };

                return result;
            });
    }

    function createCampaign(campaign, test_selector, milestone_id, report_id) {
        var queryParams = {
            test_selector: test_selector,
            milestone_id:  milestone_id,
            report_id:     report_id
        };
        return rest.all('testmanagement_campaigns')
            .post(campaign, queryParams);
    }

    function patchCampaign(campaign_id, label) {
        return rest.one('testmanagement_campaigns', campaign_id)
            .patch({
                label: label
            })
            .then(function(response) {
                return response.data;
            });
    }

    function patchExecutions(campaign_id, definition_ids, execution_ids) {
        return rest.one('testmanagement_campaigns', campaign_id)
            .one('testmanagement_executions')
            .patch({
                uuid: SharedPropertiesService.getUUID(),
                definition_ids_to_add: definition_ids,
                execution_ids_to_remove: execution_ids
            })
            .then(function(response) {
                var result = {
                    results: response.data,
                    total: response.headers('X-PAGINATION-SIZE')
                };

                return result;
            });
    }
}

