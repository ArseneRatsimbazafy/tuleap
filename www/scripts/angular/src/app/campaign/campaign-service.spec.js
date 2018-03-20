import testmanagement_module from '../app.js';
import angular from 'angular';
import 'angular-mocks';

describe ('CampaignService - ', function () {
    var mockBackend,
        CampaignService,
        SharedPropertiesService,
        userUUID = '123';

    beforeEach(function() {
        angular.mock.module(testmanagement_module);

        angular.mock.inject(function(
            _CampaignService_,
            $httpBackend,
            _SharedPropertiesService_) {
            CampaignService         = _CampaignService_;
            mockBackend             = $httpBackend;
            SharedPropertiesService = _SharedPropertiesService_;
        });

        spyOn(SharedPropertiesService, "getUUID").and.returnValue(userUUID);
    });

    afterEach (function () {
        mockBackend.verifyNoOutstandingExpectation();
        mockBackend.verifyNoOutstandingRequest();
    });

    it("getCampaigns() - ", function() {
        var campaigns = [
            {
                id: "6",
                label: "Release 1",
                status: "Open",
                nb_of_passed: 0,
                nb_of_failed: 0,
                nb_of_notrun: 1,
                nb_of_blocked: 0
            }
        ];

        mockBackend
            .expectGET('/api/v1/projects/101/testmanagement_campaigns?limit=10&offset=0&query=%7B%22status%22:%22open%22,%22milestone_id%22:0%7D')
            .respond(JSON.stringify(campaigns));

        var promise = CampaignService.getCampaigns(101, 0, "open", 10, 0);

        mockBackend.flush();

        promise.then(function(response) {
            expect(response.results.length).toEqual(1);
        });
    });

    it("createCampaign() - ", function() {
        var campaign_to_create = {
            label: 'Release',
            project_id: 101
        };
        var milestone_id       = '133';
        var report_id          = '24';
        var test_selector      = 'report';
        var campaign_created   = {
            id: 17,
            tracker: {
                id: 11,
                uri: "trackers/11",
                label: "Validation Campaign"
            },
            uri: "artifacts/17"
        };
        var expected_request =
            '/api/v1/testmanagement_campaigns' +
                '?milestone_id=' + milestone_id +
                '&report_id=' + report_id +
                '&test_selector=' + test_selector;

        mockBackend
            .expectPOST(expected_request, campaign_to_create)
            .respond(JSON.stringify(campaign_created));

        var promise = CampaignService.createCampaign(campaign_to_create, test_selector, milestone_id, report_id);

        mockBackend.flush();

        promise.then(function(response) {
            expect(response.data.id).toEqual(17);
        });
    });

    it("patchCampaign() - ", () => {
        const label = "cloiochoanitic";
        const job_configuration = {
            url: "https://example.com/badan/",
            token: "phrenicopericardiac"
        };
        const executions = [
            {
                id: 4,
                previous_result: {
                    status: "notrun"
                }
            }
        ];

        mockBackend
            .expectPATCH('/api/v1/testmanagement_campaigns/17', {
                label,
                job_configuration
            })
            .respond(executions);

        const promise = CampaignService.patchCampaign(17, label, job_configuration);

        mockBackend.flush();

        promise.then(executions => {
            expect(executions.length).toEqual(1);
        });
    });

    it("patchExecutions() - ", function() {
        var definition_ids = [1, 2],
            execution_ids = [4],
            executions = [
                {
                    id: 1,
                    previous_result: {
                        status: 'notrun'
                    }
                },
                {
                    id: 2,
                    previous_result: {
                        status: 'notrun'
                    }
                }
            ];

        mockBackend
            .expectPATCH(
                '/api/v1/testmanagement_campaigns/17/testmanagement_executions',
                {
                    uuid: userUUID,
                    definition_ids_to_add: definition_ids,
                    execution_ids_to_remove: execution_ids
                }
            )
            .respond(executions);

        var promise = CampaignService.patchExecutions(17, definition_ids, execution_ids);

        mockBackend.flush();

        promise.then(function(response) {
            expect(response.results.length).toEqual(2);
        });
    });
});

