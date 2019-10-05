<?php

$I = new ApiTester($scenario);
$I->wantTo('list all processes');

$I->signRequestAs('user', 'GET', '/processes');
$I->sendGET('/processes/');

$I->seeResponseIsJson();
$I->seeResponseCodeIs(200);

$I->seeResponseIsProcessListWith([
    ["id" => "4527288f-108e-fk69-8d2d-7914ffd93894", "title" => "Basic system and user"],
    ["id" => "98kgh356-108e-fk69-8d2d-7914ffddf45h", "title" => "Simple event trigger"],
    ["id" => "3e5c7uy5-108e-fk69-8d2d-7914ffd23w6u", "title" => "Event trigger with array of events"],
    ["id" => "cad2f7fd-8d1d-410d-8ae4-c60c0aaf05e4", "title" => "Basic system and user"],
]);
