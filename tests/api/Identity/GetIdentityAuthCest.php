<?php

class GetIdentityAuthCest
{
    /**
     * @var string
     */
    protected $identityId = 'e2d54eef-3748-4ceb-b723-23ff44a2512b';

    public function withoutSignature(\ApiTester $I)
    {
        $I->expect('the identity isn\'t returned without auth');

        $I->sendGET('/identities/' . $this->identityId);

        $I->seeResponseCodeIs(401);
    }

    public function withSignature(\ApiTester $I)
    {
        $I->expect('the identity is returned when supplying signature auth');

        $I->signRequest('GET', '/identities/' . $this->identityId);
        $I->sendGET('/identities/' . $this->identityId);

        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();
    }

    protected function underPrivilegedProvider()
    {
        return [
            ['role' => 'participant'],
            ['role' => 'stranger'],
        ];
    }

    /**
     * @dataProvider underPrivilegedProvider
     */
    public function signedAsUnderPrivileged(\ApiTester $I, \Codeception\Example $example)
    {
        $I->expect("the identity isn't returned if signed by {$example['role']}");

        $I->signRequestAs($example['role'], 'GET', '/identities/' . $this->identityId);
        $I->sendGET('/identities/' . $this->identityId);

        $I->seeResponseCodeIs(403);
    }

    protected function privilegedProvider()
    {
        return [
            ['role' => 'user'],
            ['role' => 'organization'],
        ];
    }

    /**
     * @dataProvider privilegedProvider
     */
    public function signedAsPrivileged(\ApiTester $I, \Codeception\Example $example)
    {
        $I->expect("the identity is returned if signed by {$example['role']}");

        $I->signRequestAs($example['role'], 'GET', '/identities/' . $this->identityId);
        $I->sendGET('/identities/' . $this->identityId);

        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();
    }

    public function withSignatureAndOriginalKeyId(\ApiTester $I)
    {
        $I->expect('the identity is returned when regardless of the original key');

        $I->signRequest('GET', '/identities/' . $this->identityId);
        $I->haveHttpHeader('X-Original-Key-Id', "AWDABMBzKd2oGoL8sxGxGGvL28dNzSibVkira6CHpuTX" /* stranger */);

        $I->sendGET('/identities/' . $this->identityId);

        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();
    }
}
