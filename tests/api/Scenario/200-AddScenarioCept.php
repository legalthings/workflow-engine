<?php

$I = new ApiTester($scenario);
$I->wantTo('add a scenario');

$scenario = [
    '$schema' => 'https://specs.livecontracts.io/v0.2.0/scenario/schema.json#',
    'id' => '172216a7-4b36-47e0-91ad-e27219b67331',
    'title' => 'Basic user',
    'actors' => [
        'user' => [
            'title' => 'User'
        ],
    ],
    'actions' => [
        'step1' => [
            '$schema' => 'https://specs.livecontracts.io/v0.2.0/action/schema.json#',
            'title' => 'Step1',
            'actor' => 'user',
            'responses' => [
                'ok' => [ ],
                'cancel' => [ ]
            ]
        ]
    ],
    'states' => [
        ':initial' => [
            'action' => 'step1',
            'transitions' => [
                [
                    'response' => 'ok',
                    'transition' => ':success'
                ],
                [
                    'response' => 'cancel',
                    'transition' => ':failed'
                ],
            ]
        ],
    ]
];

$I->haveHttpHeader('Content-Type', 'application/json');
$I->sendPOST('/scenarios', $scenario);

$I->seeResponseIsJson();
$I->seeResponseCodeIs(200);

$I->seeResponseIsScenario('basic-user');
