<?php

class AddIdentityAuthCest
{
    protected $identity = [
        'id' => 'da7cd9f6-e7a5-11e9-a143-d366c360f563',
        'signkeys' => [
            'default' => '5LucyTBFqSeg8qg4e33uuLY93RZqSQZjmrtsUydUNYgg'
        ]
    ];

    public function withoutSignature(\ApiTester $I)
    {
        $I->expect('the identity isn\'t added without auth');

        $I->sendPOST('/identities', $this->identity);

        $I->seeResponseCodeIs(401);
    }

    public function withSignature(\ApiTester $I)
    {
        $I->expect('the identity is added when supplying signature auth');

        $I->signRequest('POST', '/identities');
        $I->sendPOST('/identities', $this->identity);

        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();
    }

    protected function underPrivilegedProvider()
    {
        return [
            ['role' => 'participant', 'authz' => 'participant'],
            ['role' => 'stranger', 'authz' => 'participant'],
            ['role' => 'user', 'authz' => 'user'],
            ['role' => 'user', 'authz' => 'admin'],
        ];
    }

    /**
     * @dataProvider underPrivilegedProvider
     */
    public function signedAsUnderPrivileged(\ApiTester $I, \Codeception\Example $example)
    {
        $I->expect("the identity isn't returned if signed by {$example['role']}");

        $I->signRequestAs($example['role'], 'POST', '/identities');
        $I->sendPOST('/identities', $this->identity + ['authz' => $example['authz']]);

        $I->seeResponseCodeIs(403);
    }


    protected function privilegedProvider()
    {
        return [
            ['role' => 'user', 'authz' => 'participant'],
            ['role' => 'organization', 'authz' => 'participant'],
            ['role' => 'organization', 'authz' => 'user'],
            ['role' => 'organization', 'authz' => 'admin'],
        ];
    }

    /**
     * @dataProvider privilegedProvider
     */
    public function signedAsPrivileged(\ApiTester $I, \Codeception\Example $example)
    {
        $I->expect("the identity is returned if signed by {$example['role']}");

        $I->signRequestAs($example['role'], 'POST', '/identities');
        $I->sendPOST('/identities', $this->identity + ['authz' => $example['authz']]);

        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();
    }

    public function withSignatureAndOriginalKeyId(\ApiTester $I)
    {
        $I->expect('the identity is returned when regardless of the original key');

        $I->signRequest('POST', '/identities');
        $I->haveHttpHeader('X-Original-Key-Id', "AWDABMBzKd2oGoL8sxGxGGvL28dNzSibVkira6CHpuTX" /* stranger */);

        $I->sendPOST('/identities', $this->identity);

        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();
    }
}
