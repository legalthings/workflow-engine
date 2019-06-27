<?php declare(strict_types=1);

class HandshakeCest
{
    /**
     * @example { "scenario": "scenario-v1.0.json" }
     */
    public function completeAtOnce(\FlowTester $I, \Codeception\Example $example)
    {
        $I->wantTo('run a short handshake flow');

        $I->createProcessFrom($example['scenario']);

        $I->comment('verify the process after initialization');
        $I->seeCurrentStateIs('initial');
        $I->seePreviousResponsesWere([]);
        $I->seeDefaultActionIs('greet');
        $I->seeNextStatesAre([
            'wait_on_recipient',
            'wait_on_initiator',
            ':success'
        ]);

        // Step
        $I->amGoingTo("do the 'greet' action, stepping to 'wait_on_recipient'");
        $I->am('initiator');
        $I->doAction('greet', 'ok');

        $I->comment('verify the process after stepping');
        $I->seeCurrentStateIs('wait_on_recipient');
        $I->seePreviousResponsesWere(['greet.ok']);
        $I->seePreviousResponseHas('title', 'Hi, how are you?');
        $I->seeNextStatesAre([
            'wait_on_initiator', 
            ':success'
        ]);

        // Step
        $I->amGoingTo("do the 'reply' action, stepping to 'expect_sympathy'");
        $I->am('recipient');
        $I->doAction('reply', 'not_good');

        $I->comment('verify the process after stepping');
        $I->seeCurrentStateIs('expect_sympathy');
        $I->seePreviousResponsesWere(['greet.ok', 'reply.not_good']);
        $I->seePreviousResponseHas('title', 'Not so good.');
        $I->seeNextStatesAre([
            'recipient_can_elaborate',
            'expect_sympathy',
            'recipient_can_elaborate',
            ':loop'
        ]);

        // Step
        $I->amGoingTo("do the 'complete' action, stepping to ':success'");
        $I->am('initiator');
        $I->doAction('complete', 'ok', 'I understand');

        $I->comment('verify the process after stepping');
        $I->seeCurrentStateIs(':success');
        $I->seePreviousResponsesWere(['greet.ok', 'reply.not_good', 'complete.ok']);
        $I->seePreviousResponseHas('data', 'I understand');
        $I->seeNextStatesAre([]);
    }

    /**
     * @example { "scenario": "scenario-v1.0.json" }
     */
    public function longConversation(\FlowTester $I, \Codeception\Example $example)
    {
        $I->wantTo('run a long handshake flow');

        $I->createProcessFrom($example['scenario']);

        $I->comment('verify the process after initialization');
        $I->seeCurrentStateIs('initial');
        $I->seePreviousResponsesWere([]);
        $I->seeDefaultActionIs('greet');
        $I->seeNextStatesAre([
            'wait_on_recipient',
            'wait_on_initiator',
            ':success'
        ]);

        // Step
        $I->amGoingTo("do the 'greet' action, stepping to 'wait_on_recipient'");
        $I->am('initiator');
        $I->doAction('greet', 'ok');

        $I->comment('verify the process after stepping');
        $I->seeCurrentStateIs('wait_on_recipient');
        $I->seePreviousResponsesWere(['greet.ok']);
        $I->seePreviousResponseHas('title', 'Hi, how are you?');
        $I->seeNextStatesAre([
            'wait_on_initiator', 
            ':success'
        ]);

        // Step
        $I->amGoingTo("do the 'reply' action, stepping to 'expect_sympathy'");
        $I->am('recipient');
        $I->doAction('reply', 'not_good');

        $I->comment('verify the process after stepping');
        $I->seeCurrentStateIs('expect_sympathy');
        $I->seePreviousResponsesWere(['greet.ok', 'reply.not_good']);
        $I->seePreviousResponseHas('title', 'Not so good.');
        $I->seeNextStatesAre([
            'recipient_can_elaborate',
            'expect_sympathy',
            'recipient_can_elaborate',
            ':loop'
        ]);

        // Step
        $I->amGoingTo("do the 'sympathize' action, stepping to 'recipient_can_elaborate'");
        $I->am('initiator');
        $I->doAction('sympathize', 'ok');

        $I->comment('verify the process after stepping');
        $I->seeCurrentStateIs('recipient_can_elaborate');
        $I->seePreviousResponsesWere(['greet.ok', 'reply.not_good', 'sympathize.ok']);
        $I->seePreviousResponseHas('title', 'Sorry to hear that. Please tell me more.');
        $I->seeNextStatesAre([
            'expect_sympathy',
            'recipient_can_elaborate', 
            'expect_sympathy',
            ':loop'
        ]);

        // Step
        $I->amGoingTo("do the 'elaborate' action, stepping to 'expect_sympathy'");
        $I->am('recipient');
        $I->doAction('elaborate', null, 'My cat is stealing my boyfriend.');

        $I->comment('verify the process after stepping');
        $I->seeCurrentStateIs('expect_sympathy');
        $I->seePreviousResponsesWere(['greet.ok', 'reply.not_good', 'sympathize.ok', 'elaborate.ok']);
        $I->seePreviousResponseHas('data', 'My cat is stealing my boyfriend.');
        $I->seeNextStatesAre([
            'recipient_can_elaborate', 
            'expect_sympathy',
            'recipient_can_elaborate', 
            ':loop'
        ]);

        // Step
        $I->amGoingTo("do the 'sympathize' action, stepping to 'recipient_can_elaborate'");
        $I->am('initiator');
        $I->doAction('sympathize', 'ok');

        $I->comment('verify the process after stepping');
        $I->seeCurrentStateIs('recipient_can_elaborate');
        $I->seePreviousResponsesWere([
            'greet.ok', 
            'reply.not_good', 
            'sympathize.ok', 
            'elaborate.ok', 
            'sympathize.ok'
        ]);
        $I->seePreviousResponseHas('title', 'Sorry to hear that. Please tell me more.');
        $I->seeNextStatesAre([
            'expect_sympathy',
            'recipient_can_elaborate', 
            'expect_sympathy',
            ':loop'
        ]);

        // Step
        $I->amGoingTo("do the 'elaborate' action, stepping to 'expect_sympathy'");
        $I->am('recipient');
        $I->doAction('elaborate', null, 'She always comes in to cuddle with him.');

        $I->comment('verify the process after stepping');
        $I->seeCurrentStateIs('expect_sympathy');
        $I->seePreviousResponsesWere([
            'greet.ok', 
            'reply.not_good', 
            'sympathize.ok', 
            'elaborate.ok', 
            'sympathize.ok',
            'elaborate.ok'
        ]);
        $I->seePreviousResponseHas('data', 'She always comes in to cuddle with him.');
        $I->seeNextStatesAre([
            'recipient_can_elaborate', 
            'expect_sympathy',
            'recipient_can_elaborate', 
            ':loop'
        ]);

        // Step
        $I->amGoingTo("do the 'sympathize' action, stepping to 'recipient_can_elaborate'");
        $I->am('initiator');
        $I->doAction('sympathize', 'ok');

        $I->comment('verify the process after stepping');
        $I->seeCurrentStateIs('recipient_can_elaborate');
        $I->seePreviousResponsesWere([
            'greet.ok', 
            'reply.not_good', 
            'sympathize.ok', 
            'elaborate.ok', 
            'sympathize.ok',
            'elaborate.ok',
            'sympathize.ok'
        ]);
        $I->seePreviousResponseHas('title', 'Sorry to hear that. Please tell me more.');
        $I->seeNextStatesAre([
            'expect_sympathy',
            'recipient_can_elaborate', 
            'expect_sympathy',
            ':loop'
        ]);

        // Step
        $I->amGoingTo("do the 'elaborate' action, stepping to 'expect_sympathy'");
        $I->am('recipient');
        $I->doAction('elaborate', null, 'Misty has a mean purr. I know it\'s to taunt me.');

        $I->comment('verify the process after stepping');
        $I->seeCurrentStateIs('expect_sympathy');
        $I->seePreviousResponsesWere([
            'greet.ok', 
            'reply.not_good', 
            'sympathize.ok', 
            'elaborate.ok', 
            'sympathize.ok',
            'elaborate.ok',
            'sympathize.ok',
            'elaborate.ok'
        ]);
        $I->seePreviousResponseHas('data', 'Misty has a mean purr. I know it\'s to taunt me.');
        $I->seeNextStatesAre([
            'recipient_can_elaborate', 
            'expect_sympathy',
            'recipient_can_elaborate', 
            ':loop'
        ]);

        // Step
        $I->amGoingTo("do the 'complete' action, stepping to ':success'");
        $I->am('initiator');
        $I->doAction('complete', null, 'I understand');

        $I->comment('verify the process after stepping');
        $I->seeCurrentStateIs(':success');
        $I->seePreviousResponsesWere([
            'greet.ok', 
            'reply.not_good', 
            'sympathize.ok', 
            'elaborate.ok', 
            'sympathize.ok',
            'elaborate.ok',
            'sympathize.ok',
            'elaborate.ok',
            'complete.ok'
        ]);
        $I->seePreviousResponseHas('data', 'I understand');
        $I->seeNextStatesAre([]);
    }
}
