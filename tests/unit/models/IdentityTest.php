<?php

use Jasny\ValidationResult;
use LTO\Account;

/**
 * @covers Identity
 */
class IdentityTest extends \Codeception\Test\Unit
{
    /**
     * Test 'getIdProperty' method
     */
    public function testGetIdProperty()
    {
        $result = Identity::getIdProperty();

        $this->assertSame('id', $result);
    }

    /**
     * Provide data for testing 'describe' method
     *
     * @return array
     */
    public function describeProvider()
    {
        $keys = ['system' => 'system_key', 'default' => 'default_key'];

        return [
            [null, null, "unknown identity"],
            ['foo_id', null, "identity 'foo_id'"],
            ['foo_id', $keys, "identity 'foo_id'"],
            [null, $keys, "signkey 'system_key'"],
        ];
    }

    /**
     * Test 'describe' method
     *
     * @dataProvider describeProvider
     */
    public function testDescribe($id, $signkeys, $expected)
    {
        $identity = new Identity();

        $identity->id = $id;
        $identity->signkeys = $signkeys;

        $result = $identity->describe();

        $this->assertSame($expected, $result);
    }

    /**
     * Provide data for testing 'matches' method
     *
     * @return array
     */
    public function matchesProvider()
    {
        return [
            [null, null, [], [], false],
            ['foo', 'bar', [], [], false],
            [null, null, ['system' => 'foo'], [], false],
            [null, null, [], ['system' => 'foo'], false],
            [null, null, ['system' => 'foo'], ['system' => 'bar'], false],
            [null, null, ['system' => 'foo'], ['system' => 'foo', 'default' => 'bar'], false],
            ['foo', 'bar', ['system' => 'foo'], ['system' => 'foo'], false],
            ['foo', 'foo', ['system' => 'foo'], ['system' => 'bar'], false],

            [null, null, ['system' => 'foo'], ['default' => 'foo'], true],
            [null, null, ['system' => 'foo'], ['system' => 'foo'], true],
            [null, null, ['system' => 'foo', 'default' => 'bar'], ['system' => 'foo'], true],
            [null, null, ['system' => 'foo', 'default' => 'bar'], ['default' => 'bar'], true],
            [null, null, ['system' => 'foo', 'default' => 'bar'], ['system' => 'foo', 'default' => 'bar'], true],
            ['foo', 'foo', [], [], true],
            ['foo', 'foo', ['system' => 'foo'], ['system' => 'foo'], true],
        ];
    }

    /**
     * Test 'matches' method
     *
     * @dataProvider matchesProvider
     */
    public function testMatches($id1, $id2, $signkeys1, $signkeys2, $expected)
    {
        $identity1 = new Identity();
        $identity2 = new Identity();

        $identity1->id = $id1;
        $identity2->id = $id2;
        $identity1->signkeys = $signkeys1;
        $identity2->signkeys = $signkeys2;

        $result = $identity1->matches($identity2);

        $this->assertSame($expected, $result);
    }

    /**
     * Test 'fromAccount' method
     */
    public function testFromAccount()
    {
        $account = $this->createMock(Account::class);
        $account->expects($this->once())->method('getPublicSignKey')->willReturn('foo');

        $result = Identity::fromAccount($account);

        $this->assertInstanceOf(Identity::class, $result);
        $this->assertEquals(['system' => 'foo'], $result->signkeys);
    }
}
