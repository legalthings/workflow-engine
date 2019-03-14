<?php

class GetProcessWithOriginalKeyIdCest
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

    public function withValidKey(\ApiTester $I)
    {
        $I->expect('the process is returned when supplying an original key id');

        $I->signRequest('GET', '/processes/' . $this->processId);
        $I->haveHttpHeader('X-Original-Key-Id', '57FWtEbXoMKXj71FT84hcvCxN5z1CztbZ8UYJ2J49Gcn');

        $I->sendGET('/processes/' . $this->processId);

        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();

        $I->seeResponseIsProcess('basic-user-and-system');
    }

    public function withUnknownKey(\ApiTester $I)
    {
        $I->expect('the process isn\'t returned when supplying an original key that isn\'t in the process');

        $I->signRequest('GET', '/processes/' . $this->processId);
        $I->haveHttpHeader('X-Original-Key-Id', 'C47Qse1VRCGnn978WB1kqvkcsd1oG8p9SfJXUbwVZ9vV');

        $I->sendGET('/processes/' . $this->processId);
        $I->seeResponseCodeIs(403);
    }

    public function unsigned(\ApiTester $I)
    {
        $I->expect('the X-Original-Key-Id header is ignored when the request isn\'t signed');

        $I->haveHttpHeader('X-Original-Key-Id', '57FWtEbXoMKXj71FT84hcvCxN5z1CztbZ8UYJ2J49Gcn');

        $I->sendGET('/processes/' . $this->processId);
        $I->seeResponseCodeIs(401);
    }

    public function signedAsStranger(\ApiTester $I)
    {
        $I->expect('the X-Original-Key-Id header is ignored when the request is signed by a stranger');

        $I->signRequestAs('stranger', 'GET', '/processes/' . $this->processId);
        $I->haveHttpHeader('X-Original-Key-Id', '57FWtEbXoMKXj71FT84hcvCxN5z1CztbZ8UYJ2J49Gcn');

        $I->sendGET('/processes/' . $this->processId);
        $I->seeResponseCodeIs(403);
    }
}
