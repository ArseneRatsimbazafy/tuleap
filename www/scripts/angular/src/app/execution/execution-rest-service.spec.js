import trafficlights_module from '../app.js';
import angular from 'angular';
import 'angular-mocks';

describe ('ExecutionRestService - ', function () {
    var mockBackend, ExecutionRestService, SharedPropertiesService;

    beforeEach(function() {
        angular.mock.module(trafficlights_module);

        angular.mock.inject(function(
            _ExecutionRestService_,
            $httpBackend,
            _SharedPropertiesService_) {
            ExecutionRestService    = _ExecutionRestService_;
            mockBackend             = $httpBackend;
            SharedPropertiesService = _SharedPropertiesService_;
        });

        spyOn(SharedPropertiesService, "getUUID").and.returnValue('123');
    });

    afterEach (function () {
        mockBackend.verifyNoOutstandingExpectation();
        mockBackend.verifyNoOutstandingRequest();
    });

    it("getRemoteExecutions() - ", function() {
        var response = [
            {
                id: 4
            },
            {
                id: 2
            }
        ];

        mockBackend
            .expectGET('/api/v1/trafficlights_campaigns/1/trafficlights_executions?limit=10&offset=0')
            .respond (JSON.stringify(response));

        var promise = ExecutionRestService.getRemoteExecutions(1, 10, 0);

        mockBackend.flush();

        promise.then(function(executions) {
            expect(executions.results.length).toEqual(2);
        });
    });

    it("postTestExecution() - ", function() {
        var execution = {
            id: 4,
            status: "notrun"
        };

        mockBackend
            .expectPOST('/api/v1/trafficlights_executions')
            .respond(execution);

        var promise = ExecutionRestService.postTestExecution("notrun", "CentOS 5 - PHP 5.1");

        mockBackend.flush();

        promise.then(function(execution_updated) {
            expect(execution_updated.id).toBeDefined();
        });
    });

    it("putTestExecution() - ", function() {
        var execution = {
            id: 4,
            status: "passed",
            previous_result: {
                result: "",
                status: "notrun"
            }
        };

        mockBackend
            .expectPUT('/api/v1/trafficlights_executions/4?results=nothing&status=passed&time=1')
            .respond(execution);

        var promise = ExecutionRestService.putTestExecution(4, 'passed', 1, 'nothing');

        mockBackend.flush();

        promise.then(function(execution_updated) {
            expect(execution_updated.id).toBeDefined();
        });
    });

    it("changePresenceOnTestExecution() - ", function() {
        mockBackend
            .expectPATCH('/api/v1/trafficlights_executions/9/presences')
            .respond();

        var promise = ExecutionRestService.changePresenceOnTestExecution(9, 4);

        mockBackend.flush();

        promise.then(function(response) {
            expect(response.status).toEqual(200);
        });
    });

    it("linkIssue() - ", function() {
        var issueId   = 400;
        var execution = {
            id: 100,
            previous_result: {
                result: 'Something wrong'
            },
            definition: {
                summary: 'test summary',
                description: 'test description'
            }
        };

        var expectedBody = new RegExp(execution.definition.summary
                                    + ".*"
                                    + execution.definition.description);
        var matchPayload = {
            id: issueId,
            comment: {
                body  : 'MATCHING TEST SUMMARY + DESCRIPTION',
                format: 'html'
            },
            test: function(data) {
                var payload = JSON.parse(data);
                return payload.issue_id === issueId &&
                    expectedBody.test(payload.comment.body) &&
                    payload.comment.format === 'html';
            }
        };
        mockBackend
            .expectPATCH('/api/v1/trafficlights_executions/100/issues', matchPayload)
            .respond();

        var promise = ExecutionRestService.linkIssue(issueId, execution);

        mockBackend.flush();

        promise.then(function(response) {
            expect(response.status).toEqual(200);
        });
    });
});
