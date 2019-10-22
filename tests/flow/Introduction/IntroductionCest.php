<?php declare(strict_types=1);

class IntroductionCest
{
    /**
     * Basic introduction flow
     * 
     * @example { "scenario": "scenario-v0.2.json" }
     */
    public function introduction(\FlowTester $I, \Codeception\Example $example)
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

    /**
     * Make sure actor can be updated using 'current.actor' reference
     * 
     * @example { "scenario": "scenario-current-actor-update-v0.2.json" }
     */
    public function introductionCurrentActorUpdate(\FlowTester $I, \Codeception\Example $example)
    {
        $I->wantTo('run an introduction flow, updating current action actor');

        $I->createProcessFrom($example['scenario']);

        $I->comment('verify the process after initialization');
        $I->seeCurrentStateIs('initial');
        $I->seePreviousResponsesWere([]);
        $I->seeDefaultActionIs('introduce');
        $I->seeNextStatesAre(['wait_on_recipient', ':success']);

        // Step
        $I->amGoingTo("do the 'introduce' action, stepping to 'wait_on_recipient'");
        $I->am('initiator');
        $I->doAction('introduce', null, ['name' => 'Joe Smith', 'organization' => 'LTO Network']);

        $I->comment('verify the process after stepping');
        $I->seeCurrentStateIs('wait_on_recipient');
        $I->seePreviousResponsesWere(['introduce.ok']);
        $I->seePreviousResponseHas('data', ['name' => 'Joe Smith', 'organization' => 'LTO Network']);
        $I->seeActorHas('initiator', 'name', 'Joe Smith');
        $I->seeActorHas('initiator', 'organization', 'LTO Network');
        $I->seeNextStatesAre([':success']);

        // Step
        $I->amGoingTo("do the 'introduce' action, stepping to ':success'");
        $I->am('recipient');
        $I->doAction('introduce', 'ok', ['name' => 'Jane Wong', 'organization' => 'Acme Inc']);

        $I->comment('verify the process after stepping');
        $I->seeCurrentStateIs(':success');
        $I->seePreviousResponsesWere(['introduce.ok', 'introduce.ok']);
        $I->seePreviousResponseHas('data', ['name' => 'Jane Wong', 'organization' => 'Acme Inc']);
        $I->seeActorHas('recipient', 'name', 'Jane Wong');
        $I->seeActorHas('recipient', 'organization', 'Acme Inc');
        $I->seeNextStatesAre([]);
    }

    /**
     * Use action condition with reference to 'current.actor'
     * 
     * @example { "scenario": "scenario-current-actor-condition-v0.2.json" }
     */
    public function introductionCurrentActorCondition(\FlowTester $I, \Codeception\Example $example)
    {
        $I->wantTo('run an introduction flow');

        $I->createProcessFrom($example['scenario']);

        $I->comment('verify the process after initialization');
        $I->seeCurrentStateIs('initial');
        $I->seePreviousResponsesWere([]);
        $I->seeDefaultActionIs('introduce');
        $I->seeNextStatesAre(['wait_on_recipient', ':success']);

        // Step
        $I->amGoingTo("do the 'introduce' action, stepping to 'wait_on_recipient'");
        $I->am('initiator');
        $I->doAction('introduce', 'ok', ['name' => 'Joe Smith', 'organization' => 'LTO Network']);

        $I->comment('verify the process after stepping');
        $I->seeCurrentStateIs('wait_on_recipient');
        $I->seePreviousResponsesWere(['introduce.ok']);
        $I->seePreviousResponseHas('data', ['name' => 'Joe Smith', 'organization' => 'LTO Network']);
        $I->seeActorHas('initiator', 'name', 'Joe Smith');
        $I->seeActorHas('initiator', 'organization', 'LTO Network');
        $I->seeNextStatesAre([':success']);

        // Step
        $I->amGoingTo("do the 'introduce' action, stepping to ':success'");
        $I->am('recipient');
        $I->doAction('introduce', 'ok', ['name' => 'Jane Wong', 'organization' => 'Acme Inc']);

        $I->comment('verify the process after stepping');
        $I->seeCurrentStateIs(':success');
        $I->seePreviousResponsesWere(['introduce.ok', 'introduce.ok']);
        $I->seePreviousResponseHas('data', ['name' => 'Jane Wong', 'organization' => 'Acme Inc']);
        $I->seeActorHas('recipient', 'name', 'Jane Wong');
        $I->seeActorHas('recipient', 'organization', 'Acme Inc');
        $I->seeNextStatesAre([]);
    }

    /**
     * Use prettyfied format of update command for action
     * 
     * @example { "scenario": "scenario-pretty-v0.2.json" }
     */
    public function introductionPretty(\FlowTester $I, \Codeception\Example $example)
    {
        $I->wantTo('run an introduction flow with prettyfied format of update commands');

        $I->createProcessFrom($example['scenario']);

        $I->comment('verify the process after initialization');
        $I->seeCurrentStateIs('initial');
        $I->seePreviousResponsesWere([]);
        $I->seeDefaultActionIs('introduce_initiator');
        $I->seeNextStatesAre(['wait_on_recipient', 'wait_on_meeting_data', ':success']);

        // Step
        $I->amGoingTo("do the 'introduce_initiator' action, stepping to 'wait_on_recipient'");
        $I->am('initiator');
        $I->doAction('introduce_initiator', 'ok', ['name' => 'Joe Smith', 'organization' => 'LTO Network']);

        $I->comment('verify the process after stepping');
        $I->seeCurrentStateIs('wait_on_recipient');
        $I->seePreviousResponsesWere(['introduce_initiator.ok']);
        $I->seePreviousResponseHas('data', ['name' => 'Joe Smith', 'organization' => 'LTO Network']);
        $I->seeActorHas('initiator', 'name', 'Joe Smith');
        $I->seeAssetHas('initiator_info', 'name', 'Joe Smith');
        $I->seeActorHas('initiator', 'organization', 'LTO Network');
        $I->seeAssetHas('initiator_info', 'organization', 'LTO Network');
        $I->seeNextStatesAre(['wait_on_meeting_data', ':success']);

        // Step
        $I->amGoingTo("do the 'introduce_recipient' action, stepping to 'wait_on_meeting_data'");
        $I->am('recipient');
        $I->doAction('introduce_recipient', 'ok', ['name' => 'Jane Wong', 'organization' => 'Acme Inc']);

        $I->comment('verify the process after stepping');
        $I->seeCurrentStateIs('wait_on_meeting_data');
        $I->seePreviousResponsesWere(['introduce_initiator.ok', 'introduce_recipient.ok']);
        $I->seePreviousResponseHas('data', ['name' => 'Jane Wong', 'organization' => 'Acme Inc']);
        $I->seeActorHas('recipient', 'name', 'Jane Wong');
        $I->seeActorHas('recipient', 'organization', 'Acme Inc');
        $I->seeNextStatesAre([':success']);

        // Step
        $I->amGoingTo("do the 'save_meeting_data' action, stepping to ':success'");
        $I->am('initiator');
        $I->doAction('save_meeting_data', 'ok', ['meeting' => ['date' => '2018-06-25', 'comment' => 'Foo']]);

        $I->comment('verify the process after stepping');
        $I->seeCurrentStateIs(':success');
        $I->seePreviousResponsesWere(['introduce_initiator.ok', 'introduce_recipient.ok', 'save_meeting_data.ok']);
        $I->seePreviousResponseHas('data', ['meeting' => ['date' => '2018-06-25', 'comment' => 'Foo']]);
        $I->seeAssetHas('archive', 'meeting', ['date' => '2018-06-25', 'comment' => 'Foo']);
        $I->seeAssetHas('meeting_data', 'date', '2018-06-25');
        $I->seeNextStatesAre([]);
    }
}
