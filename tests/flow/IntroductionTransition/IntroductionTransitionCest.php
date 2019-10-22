<?php declare(strict_types=1);

class IntroductionTransitionCest
{
    /**
     * Check transition condition
     * 
     * @example { "scenario": "scenario-v0.2.json" }
     */
    public function transitionCondition(\FlowTester $I, \Codeception\Example $example)
    {
        $I->wantTo('run an introduction flow, testing transition condition');

        $I->createProcessFrom($example['scenario']);

        $I->comment('verify the process after initialization');
        $I->seeCurrentStateIs('initial');
        $I->seePreviousResponsesWere([]);
        $I->seeDefaultActionIs('introduce');
        $I->seeNextStatesAre([]);

        // Step
        $I->amGoingTo("do the 'introduce' action with organization name, remaining in 'initial' state");
        $I->am('initiator');
        $I->doAction('introduce', 'ok', ['organization' => 'LTO Network']);

        $I->comment('verify the process after stepping');
        $I->seeCurrentStateIs('initial');
        $I->seePreviousResponsesWere(['introduce.ok']);
        $I->seePreviousResponseHas('data', ['organization' => 'LTO Network']);
        $I->seeActorHas('initiator', 'name', null);
        $I->seeActorHas('initiator', 'organization', 'LTO Network');
        $I->seeNextStatesAre([]);

        // Step
        $I->amGoingTo("do the 'introduce' action with nonesense data, remaining in 'initial' state");
        $I->am('initiator');
        $I->doAction('introduce', 'ok', ['nonsense' => 'chatter']);

        $I->comment('verify the process after stepping');
        $I->seeCurrentStateIs('initial');
        $I->seePreviousResponsesWere(['introduce.ok', 'introduce.ok']);
        $I->seePreviousResponseHas('data', ['nonsense' => 'chatter']);
        $I->seeActorHas('initiator', 'name', null);
        $I->seeActorHas('initiator', 'organization', 'LTO Network');
        $I->seeActorHas('initiator', 'nonsense', 'chatter');
        $I->seeNextStatesAre([]);

        // Step
        $I->amGoingTo("do the 'introduce' action without data, remaining in 'initial' state");
        $I->am('initiator');
        $I->doAction('introduce');

        $I->comment('verify the process after stepping');
        $I->seeCurrentStateIs('initial');
        $I->seePreviousResponsesWere(['introduce.ok', 'introduce.ok', 'introduce.ok']);
        $I->seeActorHas('initiator', 'name', null);
        $I->seeActorHas('initiator', 'organization', 'LTO Network');
        $I->seeNextStatesAre([]);

        // Step
        $I->amGoingTo("do the 'introduce' action with name data, stepping to ':success' state");
        $I->am('initiator');
        $I->doAction('introduce', 'ok', ['name' => 'Joe Smith']);

        $I->comment('verify the process after stepping');
        $I->seeCurrentStateIs(':success');
        $I->seePreviousResponsesWere(['introduce.ok', 'introduce.ok', 'introduce.ok', 'introduce.ok']);
        $I->seePreviousResponseHas('data', ['name' => 'Joe Smith']);
        $I->seeActorHas('initiator', 'name', 'Joe Smith');
        $I->seeActorHas('initiator', 'organization', 'LTO Network');
        $I->seeNextStatesAre([]);
    }

    /**
     * Full introduction with action and state transition conditions
     * 
     * @example { "scenario": "scenario-full-v0.2.json" }
     */
    public function full(\FlowTester $I, \Codeception\Example $example)
    {
        $I->wantTo('run an introduction flow, with all kinds of conditions');

        $I->createProcessFrom($example['scenario']);

        $I->comment('verify the process after initialization');
        $I->seeCurrentStateIs('initial');
        $I->seePreviousResponsesWere([]);
        $I->seeDefaultActionIs('introduce');
        $I->seeNextStatesAre([]);

        // Step
        $I->amGoingTo("do the 'introduce' action with organization name, remaining in 'initial' state");
        $I->am('initiator');
        $I->doAction('introduce', 'ok', ['organization' => 'LTO Network']);

        $I->comment('verify the process after stepping');
        $I->seeCurrentStateIs('initial');
        $I->seePreviousResponsesWere(['introduce.ok']);
        $I->seePreviousResponseHas('data', ['organization' => 'LTO Network']);
        $I->seeActorHas('initiator', 'name', null);
        $I->seeActorHas('initiator', 'organization', 'LTO Network');
        $I->seeNextStatesAre([]);

        // Step
        $I->amGoingTo("do the 'introduce' action with name data, stepping to ':success' state");
        $I->am('initiator');
        $I->doAction('introduce', 'ok', ['name' => 'Joe Smith']);

        $I->comment('verify the process after stepping');
        $I->seeCurrentStateIs('wait_on_recipient');
        $I->seePreviousResponsesWere(['introduce.ok', 'introduce.ok']);
        $I->seePreviousResponseHas('data', ['name' => 'Joe Smith']);
        $I->seeActorHas('initiator', 'name', 'Joe Smith');
        $I->seeActorHas('initiator', 'organization', 'LTO Network');
        $I->seeNextStatesAre([]);

        // Step
        $I->amGoingTo("do the 'introduce' action for with organization name for recipient, remaining in 'wait_on_recipient' state");
        $I->am('recipient');
        $I->doAction('introduce', 'ok', ['organization' => 'Acme Inc']);

        $I->comment('verify the process after stepping');
        $I->seeCurrentStateIs('wait_on_recipient');
        $I->seePreviousResponsesWere(['introduce.ok', 'introduce.ok', 'introduce.ok']);
        $I->seePreviousResponseHas('data', ['organization' => 'Acme Inc']);
        $I->seeActorHas('recipient', 'name', null);
        $I->seeActorHas('recipient', 'organization', 'Acme Inc');
        $I->seeNextStatesAre([]);

        // Step
        $I->amGoingTo("do the 'introduce' action with name data for recipient, stepping to ':success' state");
        $I->am('recipient');
        $I->doAction('introduce', 'ok', ['name' => 'Jane Wong']);

        $I->comment('verify the process after stepping');
        $I->seeCurrentStateIs(':success');
        $I->seePreviousResponsesWere(['introduce.ok', 'introduce.ok', 'introduce.ok', 'introduce.ok']);
        $I->seePreviousResponseHas('data', ['name' => 'Jane Wong']);
        $I->seeActorHas('recipient', 'name', 'Jane Wong');
        $I->seeActorHas('recipient', 'organization', 'Acme Inc');
        $I->seeNextStatesAre([]);
    }    
}
