<?php

class DeleteIdentityAuthCest
{
    /**
     * @var string
     */
    protected $identityId = 'e2d54eef-3748-4ceb-b723-23ff44a2512b';

    public function withoutSignature(\ApiTester $I)
    {
        $I->expect('the identity isn\'t returned without auth');

        $I->sendDELETE('/identities/' . $this->identityId);

        $I->seeResponseCodeIs(401);
    }

    public function withSignature(\ApiTester $I)
    {
        $I->expect('the identity is returned when supplying signature auth');

        $I->signRequest('DELETE', '/identities/' . $this->identityId);
        $I->sendDELETE('/identities/' . $this->identityId);

        $I->seeResponseCodeIs(204);
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

        $I->signRequestAs($example['role'], 'DELETE', '/identities/' . $this->identityId);
        $I->sendDELETE('/identities/' . $this->identityId);

        $I->seeResponseCodeIs(403);
    }

    public function signedAsAdmin(\ApiTester $I)
    {
        $I->expect("the identity is returned if signed by organization");

        $I->signRequestAs('organization', 'DELETE', '/identities/' . $this->identityId);
        $I->sendDELETE('/identities/' . $this->identityId);

        $I->seeResponseCodeIs(204);
    }

    public function withSignatureAndOriginalKeyId(\ApiTester $I)
    {
        $I->expect('the identity is returned when regardless of the original key');

        $I->signRequest('DELETE', '/identities/' . $this->identityId);
        $I->haveHttpHeader('X-Original-Key-Id', "AWDABMBzKd2oGoL8sxGxGGvL28dNzSibVkira6CHpuTX" /* stranger */);

        $I->sendDELETE('/identities/' . $this->identityId);

        $I->seeResponseCodeIs(204);
    }
}
