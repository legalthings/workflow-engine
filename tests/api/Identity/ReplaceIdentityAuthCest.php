<?php

use Codeception\Example;

class ReplaceIdentityAuthCest
{
    protected $newSignkey = '5LucyTBFqSeg8qg4e33uuLY93RZqSQZjmrtsUydUNYgg';

    protected function identity(string $id, ?string $authz = null): array
    {
        return [
            'id' => $id,
            'signkeys' => [
                'default' => $this->newSignkey
            ],
        ] + ($authz !== null ? ['authz' => $authz] : []);
    }

    protected function identityProvider()
    {
        return [
            'participant' => ['is' => 'participant', 'id' => '14134336-e5e8-11e9-b414-778e97bfed1a'],
            'user'        => ['is' => 'user', 'id' => 'e2d54eef-3748-4ceb-b723-23ff44a2512b'],
            'admin'       => ['is' => 'admin', 'id' => '6uk7288s-afe4-7398-8dbh-7914ffd930pl'],
        ];
    }

    /**
     * @dataProvider identityProvider
     */
    public function withoutSignature(\ApiTester $I, Example $example)
    {
        $I->expect('the identity isn\'t added without auth');

        $I->sendPOST('/identities', $this->identity($example['id']));

        $I->seeResponseCodeIs(401);
    }

    /**
     * @dataProvider identityProvider
     */
    public function withSignature(\ApiTester $I, Example $example)
    {
        $I->expect('the identity is added when supplying signature auth');

        $I->signRequest('POST', '/identities');
        $I->sendPOST('/identities', $this->identity($example['id']));

        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();
    }

    protected function underPrivilegedProvider()
    {
        $existing = $this->identityProvider();

        return [
            ['role' => 'participant', 'authz' => 'participant'] + $existing['participant'],
            ['role' => 'stranger', 'authz' => 'participant'] + $existing['participant'],
            ['role' => 'user', 'authz' => 'user'] + $existing['participant'],
            ['role' => 'user', 'authz' => 'admin'] + $existing['participant'],
            ['role' => 'user', 'authz' => 'participant'] + $existing['user'],
            ['role' => 'user', 'authz' => 'participant'] + $existing['admin'],
        ];
    }

    /**
     * @dataProvider underPrivilegedProvider
     */
    public function signedAsUnderPrivileged(\ApiTester $I, Example $example)
    {
        $I->expect("the identity isn't returned if signed by {$example['role']}");

        $I->signRequestAs($example['role'], 'POST', '/identities');
        $data = $this->identity($example['id'], $example['authz']);
        $I->sendPOST('/identities', $data);

        $I->seeResponseCodeIs(403);
    }


    protected function privilegedProvider()
    {
        $existing = $this->identityProvider();

        return [
            ['role' => 'user', 'authz' => 'participant'] + $existing['participant'],
            ['role' => 'organization', 'authz' => 'participant'] + $existing['participant'],
            ['role' => 'organization', 'authz' => 'user'] + $existing['user'],
            ['role' => 'organization', 'authz' => 'admin'] + $existing['admin'],
        ];
    }

    /**
     * @dataProvider privilegedProvider
     */
    public function signedAsPrivileged(\ApiTester $I, Example $example)
    {
        $I->expect("the identity is returned if signed by {$example['role']}");

        $I->signRequestAs($example['role'], 'POST', '/identities');
        $I->sendPOST('/identities', $this->identity($example['id'], $example['authz']));

        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();
    }

    /**
     * @dataProvider identityProvider
     */
    public function withSignatureAndOriginalKeyId(\ApiTester $I, Example $example)
    {
        $I->expect('the identity is returned when regardless of the original key');

        $I->signRequest('POST', '/identities');
        $I->haveHttpHeader('X-Original-Key-Id', "AWDABMBzKd2oGoL8sxGxGGvL28dNzSibVkira6CHpuTX" /* stranger */);

        $I->sendPOST('/identities', $this->identity($example['id']));

        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();
    }
}
