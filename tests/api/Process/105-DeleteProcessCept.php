<?php

$I = new ApiTester($scenario);
$I->wantTo('delete process');

$I->amSignatureAuthenticated("PIw+8VW129YY/6tRfThI3ZA0VygH4cYWxIayUZbdA3I9CKUdmqttvVZvOXN5BX2Z9jfO3f1vD1/R2jxwd3BHBw==");

$id = '4527288f-108e-fk69-8d2d-7914ffd93894';
$I->seeInCollection('processes', ['_id' => $id]);

$I->sendDELETE('/processes/' . $id);
$I->seeResponseCodeIs(200);

$I->dontSeeInCollection('processes', ['_id' => $id]);
