<?php

$I = new ApiTester($scenario);
$I->wantTo('start a process, using identity for auth');

$path = '/processes';
$headers = [
    'date' => date(DATE_RFC1123),
    'digest' => 'foo-bar',
    'Content-Type' => 'application/x-www-form-urlencoded',
    'Content-Length' => '45'
];

$request = $I->getSignedRequest('post', $path, $headers);

$I->haveHttpHeader('X-Identity', '6uk7288s-afe4-7398-8dbh-7914ffd930pl');
$I->haveHttpHeader('date', $request->getHeaderLine('date'));
$I->haveHttpHeader('digest', $request->getHeaderLine('digest'));
$I->haveHttpHeader('Content-Type', $request->getHeaderLine('content-type'));
$I->haveHttpHeader('Content-Length', $request->getHeaderLine('content-length'));
$I->haveHttpHeader('authorization', $request->getHeaderLine('authorization'));

$I->sendPOST($path, ['scenario' => '2557288f-108e-4398-8d2d-7914ffd93150']);

$I->seeResponseCodeIs(200);
$I->seeResponseIsJson();

$I->seeResponseJsonMatchesJsonPath('$.id');
$I->seeResponseContainsJson(['scenario' => '2557288f-108e-4398-8d2d-7914ffd93150']);
