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
        $identity = $this->createConfiguredMock(Identity::class, ['describe' => "identity 'a'"]);

        $values = [
            'title' => 'John Doe',
            'key' => 'john_doe',
            'identity' => $identity
        ];

        return [
            [[], "unknown actor"],
            [array_only($values, ['title']), 'John Doe'],
            [array_only($values, ['key']), "actor 'john_doe'"],
            [array_only($values, ['identity']), "actor with identity 'a'"],
            [$values, 'John Doe'],
            [array_without($values, ['title']), "actor 'john_doe'"],
            [array_without($values, ['title', 'key']), "actor with identity 'a'"]
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
        $identity1 = $this->createMock(Identity::class);
        $identity2 = $this->createMock(Identity::class);
        $identity3 = $this->createMock(Identity::class);

        $identity1->expects($this->any())->method('matches')
            ->withConsecutive([$identity2], [$identity3])->willReturnOnConsecutiveCalls(true, false);

        return [
            [['key' => 'foo'], ['key' => 'foo'], true],
            [['key' => 'foo'], ['key' => 'bar'], false],
            [['identity' => $identity1], ['identity' => $identity2], true],
            [['identity' => $identity1], ['identity' => $identity3], false],
            [['identity' => $identity1], ['identity' => null], false],
            [['identity' => null], ['identity' => $identity1], false],
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
