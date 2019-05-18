<?php declare(strict_types=1);

class IntroductionCest
{
    /**
     * @example { "scenario": "scenario-v1.0.json" }
     */
    public function updateUser(\FlowTester $I, \Codeception\Example $example)
    {
        $I->wantTo('run an introduction flow');

        $I->createProcessFrom($example['scenario']);

        $I->comment('verify the process after initialization');
        $I->seeCurrentStateIs('initial');
        $I->seePreviousResponsesWere([]);
        $I->seeDefaultActionIs('introduce_initiator');
        $I->seeNextStatesAre(['wait_on_recipient', ':success']);

        // Step
        $I->amGoingTo("do the 'introduce_initiator' action, stepping to 'wait_on_recipient'");
        $I->am('initiator');
        $I->doAction('introduce_initiator', 'ok', ['name' => 'Joe Smith', 'organization' => 'LTO Network']);

        $I->comment('verify the process after stepping');
        $I->seeCurrentStateIs('wait_on_recipient');
        $I->seePreviousResponsesWere(['introduce_initiator.ok']);
        $I->seePreviousResponseHas('data', ['name' => 'Joe Smith', 'organization' => 'LTO Network']);
        $I->seeActorHas('initiator', 'name', 'Joe Smith');
        $I->seeActorHas('initiator', 'organization', 'LTO Network');
        $I->seeNextStatesAre([':success']);

        // Step
        $I->amGoingTo("do the 'introduce_recipient' action, stepping to ':success'");
        $I->am('recipient');
        $I->doAction('introduce_recipient', 'ok', ['name' => 'Jane Wong', 'organization' => 'Acme Inc']);

        $I->comment('verify the process after stepping');
        $I->seeCurrentStateIs(':success');
        $I->seePreviousResponsesWere(['introduce_initiator.ok', 'introduce_recipient.ok']);
        $I->seePreviousResponseHas('data', ['name' => 'Jane Wong', 'organization' => 'Acme Inc']);
        $I->seeActorHas('recipient', 'name', 'Jane Wong');
        $I->seeActorHas('recipient', 'organization', 'Acme Inc');
        $I->seeNextStatesAre([]);
    }
}
