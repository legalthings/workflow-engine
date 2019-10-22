<?php declare(strict_types=1);

use GuzzleHttp\Psr7\Response as HttpResponse;

class BasicSystemAndUserCest
{
    /**
     * @example { "scenario": "scenario-v0.2.json" }
     */
    public function stepOk(\FlowTester $I, \Codeception\Example $example)
    {
        $I->wantTo('run a system flow after which the user must perform an action');

        $I->createProcessFrom($example['scenario']);

        $I->comment('verify the process after initialization');
        $I->seeCurrentStateIs('initial');
        $I->seePreviousResponsesWere([]);
        $I->seeDefaultActionIs('step1');
        $I->seeNextStatesAre(['step2', 'step3', ':success']);

        $I->am('system');
        $I->amGoingTo("invoke the trigger of the 'step1' action");
        $I->expectHttpRequest(new HttpResponse(200, [],'response body'));
        $I->invokeTrigger();
        $I->seeTheNumberOfHttpRequestWere(1);

        $I->seeCurrentStateIs('step2');
        $I->seePreviousResponsesWere(['step1.ok']);
        $I->seePreviousResponseHas('data', 'response body');
        $I->seeDefaultActionIs('step2');
        $I->seeNextStatesAre(['step3', ':success']);

        $I->am('system');
        $I->amGoingTo("invoke the trigger of the 'step2' action");
        $I->invokeTrigger();

        $I->seeCurrentStateIs('step3');
        $I->seePreviousResponsesWere(['step1.ok', 'step2.ok']);
        $I->seePreviousResponseHas('data', 'second response');
        $I->seeNextStatesAre([':success']);

        $I->am('user');
        $I->amGoingTo("do the 'step3' action, stepping to ':success'");
        $I->doAction('step3', 'ok', 'the users says hi');

        $I->comment('verify the process after stepping');
        $I->seePreviousResponsesWere(['step1.ok', 'step2.ok', 'step3.ok']);
        $I->seeCurrentStateIs(':success');
        $I->seePreviousResponseHas('data', 'the users says hi');
        $I->seeNextStatesAre([]);
    }
}
