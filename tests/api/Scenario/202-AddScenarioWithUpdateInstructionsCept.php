<?php

$I = new ApiTester($scenario);
$I->wantTo('add a scenario with prettified update instructions');

$scenario = $I->getEntityDump('scenarios', 'basic-user-and-system.update-instructions');
$scenario['id'] = 'sdef456h-108e-3652-8d2d-7914ffd3erdb';

$I->haveHttpHeader('Content-Type', 'application/json');
$I->sendPOST('/scenarios', $scenario);

$I->seeResponseIsJson();
$I->seeResponseCodeIs(200);

$I->expectTo('see that update instructions were saved correctly');

$I->haveHttpHeader('Accept', 'application/json;view=complete');
$I->sendGET('/scenarios/' . $scenario['id']);
$I->seeResponseIsJson();
$I->seeResponseCodeIs(200);

$expected = $I->getEntityDump('scenarios', 'basic-user-and-system.update-instructions.not-pretty');

$I->seeResponseProcessHas('actions.step1.responses.ok.update', [
    ['select' => 'foo', 'patch' => true, 'data' => null, 'projection' => null],
    ['select' => 'baz', 'patch' => true, 'data' => null, 'projection' => null],
    ['select' => 'bar', 'patch' => false, 'data' => null, 'projection' => null]
]);

$I->seeResponseProcessHas('actions.step2.responses.ok.update', [
    ['select' => 'bar', 'patch' => true, 'data' => null, 'projection' => null]
]);

$I->seeResponseProcessHas('actions.step3.responses.ok.update', [
    ['select' => 'bar', 'patch' => true, 'data' => null, 'projection' => '{id: test}']
]);
