<?php

$I = new ApiTester($scenario);
$I->wantTo('add a scenario');

$scenario = [
    "schema" => "https://specs.livecontracts.io/v1.0.0/scenario/schema.json#",
    "title" => "Added basic system and user",
    "actors" => [
        [
            "key" => "user",
            "title" => "User"
        ],
        [
            "key" => "system",
            "title" => "System"
        ]
    ],
    "actions" => [
        [
            "schema" => "https://specs.livecontracts.io/v1.0.0/action/http/schema.json#",
            "key" => "step1",
            "title" => "Step1",
            "actor" => "system",
            "url" => "https://www.example.com",
            "responses" => [
                "ok" => [ ],
                "error" => [ ]
            ]
        ],
        [
            "schema" => "https://specs.livecontracts.io/v1.0.0/action/nop/schema.json#",
            "key" => "step2",
            "title" => "Step2",
            "trigger_response" => "ok",
            "data" => "second response",
            "actor" => "system",
            "responses" => [
                "ok" => [ ],
                "error" => [ ]
            ]
        ],
        [
            "schema" => "https://specs.livecontracts.io/v1.0.0/action/schema.json#",
            "key" => "step3",
            "title" => "Step3",
            "actor" => "user",
            "responses" => [
                "ok" => [ ],
                "cancel" => [ ]
            ]
        ]
    ],
    "states" => [
        [
            "key" => ":initial",
            "actions" => ["step1"],
            "transitions" => [
                [
                    "action" => "step1",
                    "response" => "ok",
                    "transition" => "step2"
                ],
                [
                    "action" => "step1",
                    "response" => "error",
                    "transition" => ":failed"
                ]
            ]
        ],
        [
            "key" => "second",
            "actions" => ["step2"],
            "transitions" => [
                [
                    "action" => "step2",
                    "response" => "ok",
                    "transition" => "step3"
                ],
                [
                    "action" => "step2",
                    "response" => "error",
                    "transition" => ":failed"
                ]
            ]
        ],
        [
            "key" => "third",
            "actions" => ["step3"],
            "transitions" => [
                [
                    "transition" => ":success"
                ]
            ]
        ]
    ]
];

$I->haveHttpHeader('Content-Type', 'application/json');
$I->sendPOST('/scenarios', $scenario);

$I->seeResponseCodeIs(200);
$I->seeResponseIsJson();

// TODO Move this to API helper
$expectedJson = file_get_contents(__DIR__ . '/../../_data/scenarios/basic-user-and-system.json');
$expected = json_decode($expectedJson, true);

unset($expected['id']);
$expected['title'] = 'Added basic system and user';

$I->canSeeResponseContainsJson($expected);
$I->seeResponseJsonMatchesJsonPath('$.id');
