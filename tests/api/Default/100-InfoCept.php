<?php

$I = new ApiTester($scenario);
$I->wantTo('tests the system info response');

$I->sendGET('/');
$I->seeResponseCodeIs(200);
$I->seeResponseIsJson();

$I->seeResponseContainsJson(['name' => 'legalthings/workflow-engine']);
$I->seeResponseContainsJson(['env' => 'tests']);
