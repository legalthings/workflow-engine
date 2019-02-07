<?php declare(strict_types=1);

use GuzzleHttp\Psr7\Response as HttpResponse;

class BasicSystemCest
{
    /**
     * @example { "scenario": "scenario-v1.0.json" }
     */
    public function stepOk(\FlowTester $I, \Codeception\Example $example)
    {
        $I->wantTo('run a basic system flow responding with ok');

        $I->createProcessFrom($example['scenario']);

        $I->comment('verify the process after initialization');
        $I->seeCurrentStateIs(':initial');
        $I->seePreviousResponsesWere([]);
        $I->seeDefaultActionIs('step1');
        $I->seeNextStatesAre(['step2', ':success']);

        $I->amGoingTo("invoke the trigger of the 'step1' action");
        $I->expectHttpRequest(new HttpResponse(200, [],'response body'));
        $I->invokeTrigger();

        $I->seeCurrentStateIs('step2');
        $I->seePreviousResponsesWere(['step1.ok']);
        $I->seePreviousResponseHas('data', 'response body');
        $I->seeDefaultActionIs('step2');
        $I->seeNextStatesAre([':success']);

        $I->amGoingTo("invoke the trigger of the 'step2' action");
        $I->invokeTrigger();

        $I->seeCurrentStateIs(':success');
        $I->seePreviousResponsesWere(['step1.ok', 'step2.ok']);
        $I->seePreviousResponseHas('data', 'second response');
        $I->seeNextStatesAre([]);
    }

    /**
     * @example { "scenario": "scenario-v1.0.json" }
     */
    public function stepError(\FlowTester $I, \Codeception\Example $example)
    {
        $I->wantTo('run a basic system flow responding with error');

        $I->createProcessFrom($example['scenario']);

        $I->comment('verify the process after initialization');
        $I->seeCurrentStateIs(':initial');
        $I->seePreviousResponsesWere([]);
        $I->seeDefaultActionIs('step1');
        $I->seeNextStatesAre(['step2', ':success']);

        $I->amGoingTo("invoke the trigger of the 'step1' action");
        $I->expectHttpRequest(new HttpResponse(400, [],'error response'));
        $I->invokeTrigger();

        $I->seeCurrentStateIs(':failed');
        $I->seePreviousResponsesWere(['step1.error']);
        $I->seePreviousResponseHas('data', "Action 'step1' failed: error response");
        $I->seeNextStatesAre([]);
    }
}
