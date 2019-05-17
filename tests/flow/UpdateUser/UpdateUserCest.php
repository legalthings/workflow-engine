<?php declare(strict_types=1);

class UpdateUserCest
{
    /**
     * @example { "scenario": "scenario-v1.0.json" }
     */
    public function updateUser(\FlowTester $I, \Codeception\Example $example)
    {
        $I->wantTo('run an update user flow');

        $I->createProcessFrom($example['scenario']);

        $I->comment('verify the process after initialization');
        $I->seeCurrentStateIs('initial');
        $I->seePreviousResponsesWere([]);
        $I->seeDefaultActionIs('introduction');
        $I->seeNextStatesAre([':success']);

        // Step
        $I->amGoingTo("do the 'introduction' action, stepping to ':success'");
        $I->am('initiator');
        $I->doAction('introduction', 'ok', ['name' => 'Joe Smith', 'organization' => 'LTO Network']);

        $I->comment('verify the process after stepping');
        $I->seeCurrentStateIs(':success');
        $I->seePreviousResponsesWere(['introduction.ok']);
        $I->seePreviousResponseHas('data', ['name' => 'Joe Smith', 'organization' => 'LTO Network']);
        $I->seeActorHas('initiator', 'name', 'Joe Smith');
        $I->seeActorHas('initiator', 'organization', 'LTO Network');
        $I->seeNextStatesAre([]);
    }
}
