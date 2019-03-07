<?php

use Jasny\ValidationResult;

/**
 * @covers Actor
 */
class ActorTest extends \Codeception\Test\Unit
{
    /**
     * @var Actor
     **/
    protected $actor;

    /**
     * Execute before each test case
     */
    public function _before()
    {
        $this->actor = new Actor();
    }

    /**
     * Provide data for testing 'describe' method
     *
     * @return array
     */
    public function describeProvider()
    {
        $values = [
            'title' => 'John Doe',
            'key' => 'john_doe',
            'identity' => 'doe identity',
            'signkeys' => ['foo', 'bar']
        ];

        return [
            [array_only($values, ['title']), 'John Doe'],
            [array_only($values, ['key']), "actor 'john_doe'"],
            [array_only($values, ['identity']), "actor with identity 'doe identity'"],
            [array_only($values, ['signkeys']), "actor with signkey 'foo'/'bar'"],
            [$values, 'John Doe'],
            [array_without($values, ['title']), "actor 'john_doe'"],
            [array_without($values, ['title', 'key']), "actor with identity 'doe identity'"]
        ];
    }

    /**
     * Test 'describe' method
     *
     * @dataProvider describeProvider
     */
    public function testDescribe($values, $expected)
    {
        $this->actor->setValues($values);

        $result = $this->actor->describe();

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
            [['key' => 'foo'], ['key' => 'foo'], true],
            [['key' => 'foo'], ['key' => 'bar'], false],
            [['identity' => 'foo'], ['identity' => 'foo'], true],
            [['identity' => 'foo'], ['identity' => 'bar'], false],
            [['signkeys' => ['foo', 'bar', 'baz']], ['signkeys' => ['foo', 'baz']], true],
            [['signkeys' => ['foo', 'bar', 'baz']], ['signkeys' => ['foo', 'baz', 'zet']], false],
            [[], [], false]
        ];
    }

    /**
     * Test 'matches' method
     *
     * @dataProvider matchesProvider
     */
    public function testMatches($values, $compareValues, $expected)
    {
        $this->actor->setValues($values);

        $actorCompare = new Actor();
        $actorCompare->setValues($compareValues);

        $result = $this->actor->matches($actorCompare);

        $this->assertSame($expected, $result);
    }
}
