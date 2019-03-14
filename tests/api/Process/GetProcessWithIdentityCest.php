<?php

class GetProcessWithIdentityCest
{
    /**
     * This is a process where our system isn't involved.
     * @var string
     */
    protected $processId = '4527288f-108e-fk69-8d2d-7914ffd93894';

    public function withoutSignature(\ApiTester $I)
    {
        $I->expect('the process isn\'t returned without auth');

        $I->sendGET('/processes/' . $this->processId);
        $I->seeResponseCodeIs(401);
    }

    public function withOnlySignature(\ApiTester $I)
    {
        $I->expect('the process isn\'t returned because system is not part of it');

        $I->signRequest('GET', '/processes/' . $this->processId);

        $I->sendGET('/processes/' . $this->processId);
        $I->seeResponseCodeIs(403);
    }

    public function withIdentity(\ApiTester $I)
    {
        $I->expect('the process is returned when supplying an identity');

        $I->signRequest('GET', '/processes/' . $this->processId);
        $I->haveHttpHeader('X-Identity', '6uk7288s-afe4-7398-8dbh-7914ffd930pl');

        $I->sendGET('/processes/' . $this->processId);

        $I->seeResponseIsJson();
        $I->seeResponseCodeIs(200);

        $I->seeResponseIsProcess('basic-user-and-system');
    }

    public function withUnknownIdentity(\ApiTester $I)
    {
        $I->expect('the process isn\'t returned when supplying an identity that isn\'t in the process');

        $I->signRequest('GET', '/processes/' . $this->processId);
        $I->haveHttpHeader('X-Identity', 'e2d54eef-3748-4ceb-b723-23ff44a2512b');

        $I->sendGET('/processes/' . $this->processId);
        $I->seeResponseCodeIs(403);
    }

    public function unsigned(\ApiTester $I)
    {
        $I->expect('the X-Identity header is ignored when the request isn\'t signed');

        $I->haveHttpHeader('X-Identity', '6uk7288s-afe4-7398-8dbh-7914ffd930pl');

        $I->sendGET('/processes/' . $this->processId);
        $I->seeResponseCodeIs(401);
    }

    public function signedAsStranger(\ApiTester $I)
    {
        $I->expect('the X-Identity header is ignored when the request is signed by a stranger');

        $I->signRequestAs('stranger', 'GET', '/processes/' . $this->processId);
        $I->haveHttpHeader('X-Identity', '6uk7288s-afe4-7398-8dbh-7914ffd930pl');

        $I->sendGET('/processes/' . $this->processId);
        $I->seeResponseCodeIs(403);
    }
}
