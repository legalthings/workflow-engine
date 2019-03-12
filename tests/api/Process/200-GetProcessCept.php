<?php

$I = new ApiTester($scenario);
$I->wantTo('get a process');

$I->amSignatureAuthenticated("PIw+8VW129YY/6tRfThI3ZA0VygH4cYWxIayUZbdA3I9CKUdmqttvVZvOXN5BX2Z9jfO3f1vD1/R2jxwd3BHBw==");

$I->sendGET('/processes/4527288f-108e-fk69-8d2d-7914ffd93894');

$I->seeResponseIsJson();
$I->seeResponseCodeIs(200);

$I->seeResponseIsProcess('basic-user-and-system');
