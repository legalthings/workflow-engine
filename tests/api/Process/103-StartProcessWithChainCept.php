<?php

$I = new ApiTester($scenario);
$I->wantTo('start a process, passing scenario id');

$I->amSignatureAuthenticated("PIw+8VW129YY/6tRfThI3ZA0VygH4cYWxIayUZbdA3I9CKUdmqttvVZvOXN5BX2Z9jfO3f1vD1/R2jxwd3BHBw==");

$I->sendPOST('/processes', [
    'id' => '2z4AmxL122aaTLyVy6rhEfXHGJMGuUnViUhw3D7XC4VcycnkEwkHXXdxg73vLb',
    'scenario' => ['id' => '2557288f-108e-4398-8d2d-7914ffd93150'],
    'chain' => ['id' => '2b6QYLttL2R3CLGL4fUB9vaXXX4c5PRhHhCS51CZQodgu7ay9BpMNdJ6mZ8hyF'],
]);

$I->seeResponseIsJson();
$I->seeResponseCodeIs(200);

$I->seeResponseContainsJson(['id' => '2z4AmxL122aaTLyVy6rhEfXHGJMGuUnViUhw3D7XC4VcycnkEwkHXXdxg73vLb']);
$I->seeResponseContainsJson(['scenario' => '2557288f-108e-4398-8d2d-7914ffd93150']);
$I->seeResponseContainsJson(['chain' => '2b6QYLttL2R3CLGL4fUB9vaXXX4c5PRhHhCS51CZQodgu7ay9BpMNdJ6mZ8hyF']);
