<?php

class AddIdentityAuthCest
{
    /**
     * @var string
     */
    protected $identity = [
        'id' => 'e2d54eef-3748-4ceb-b723-23ff44a2512b',
        'signkeys' => [
            'default' => '5LucyTBFqSeg8qg4e33uuLY93RZqSQZjmrtsUydUNYgg'
        ],
        'authz' => 'participant',
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
            ['role' => 'participant'],
            ['role' => 'user'],
            ['role' => 'stranger'],
        ];
    }

    /**
     * @dataProvider underPrivilegedProvider
     */
    public function signedAsUnderPrivileged(\ApiTester $I, \Codeception\Example $example)
    {
        $I->expect("the identity isn't returned if signed by {$example['role']}");

        $I->signRequestAs($example['role'], 'POST', '/identities');
        $I->sendPOST('/identities', $this->identity);

        $I->seeResponseCodeIs(403);
    }

    public function signedAsAdmin(\ApiTester $I)
    {
        $I->expect("the identity is returned if signed by organization");

        $I->signRequestAs('organization', 'POST', '/identities');
        $I->sendPOST('/identities', $this->identity);

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
