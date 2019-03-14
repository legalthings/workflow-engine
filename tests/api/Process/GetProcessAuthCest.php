<?php

class GetProcessAuthCest
{
    /**
     * This is a process where our system is involved in as 'system' actor.
     * @var string
     */
    protected $processId = 'cad2f7fd-8d1d-410d-8ae4-c60c0aaf05e4';

    public function withoutSignature(\ApiTester $I)
    {
        $I->expect('the process isn\'t returned without auth');

        $I->sendGET('/processes/' . $this->processId);
        $I->seeResponseCodeIs(401);
    }

    public function withSignature(\ApiTester $I)
    {
        $I->expect('the process is returned when supplying signature auth');

        $I->signRequest('GET', '/processes/' . $this->processId);

        $I->sendGET('/processes/' . $this->processId);

        $I->seeResponseIsJson();
        $I->seeResponseCodeIs(200);

        $I->seeResponseIsProcess('basic-user-and-system.success');
    }

    public function signedAsStranger(\ApiTester $I)
    {
        $I->expect('the process isn\'t returned as user isn\'t part of it');

        $I->signRequestAs('stranger', 'GET', '/processes/' . $this->processId);

        $I->sendGET('/processes/' . $this->processId);
        $I->seeResponseCodeIs(403);
    }
}
