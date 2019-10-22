<?php declare(strict_types=1);

class BasicUserCest
{
    /**
     * @example { "scenario": "scenario-v0.2.json" }
     */
    public function stepOk(\FlowTester $I, \Codeception\Example $example)
    {
        $I->wantTo('run a basic user flow responding with ok');

        $I->createProcessFrom($example['scenario']);

        $I->comment('verify the process after initialization');
        $I->seeCurrentStateIs('initial');
        $I->seePreviousResponsesWere([]);
        $I->seeDefaultActionIs('step1');
        $I->seeNextStatesAre([':success']);

        $I->amGoingTo("do the 'step1' action, stepping to ':success'");
        $I->am('user1');
        $I->doAction('step1', 'ok', 'the users says hi');

        $I->comment('verify the process after stepping');
        $I->seeCurrentStateIs(':success');
        $I->seePreviousResponsesWere(['step1.ok']);
        $I->seePreviousResponseHas('data', 'the users says hi');
        $I->seeNextStatesAre([]);
    }

    /**
     * @example { "scenario": "scenario-v0.2.json" }
     */
    public function stepCancel(\FlowTester $I, \Codeception\Example $example)
    {
        $I->wantTo('run a basic user flow responding with cancel');

        $I->createProcessFrom($example['scenario']);

        $I->comment('verify the process after initialization');
        $I->seeCurrentStateIs('initial');
        $I->seePreviousResponsesWere([]);
        $I->seeDefaultActionIs('step1');
        $I->seeNextStatesAre([':success']);

        $I->amGoingTo("cancel the 'step1' action, stepping to ':failed'");
        $I->am('user1');
        $I->doAction('step1', 'cancel', 'the users says no');

        $I->comment('verify the process after stepping');
        $I->seeCurrentStateIs(':failed');
        $I->seePreviousResponsesWere(['step1.cancel']);
        $I->seePreviousResponseHas('data', 'the users says no');
        $I->seeNextStatesAre([]);
    }
}
