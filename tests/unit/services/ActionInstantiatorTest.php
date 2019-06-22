<?php

use LegalThings\DataEnricher;

/**
 * @covers ActionInstantiator
 */
class ActionInstantiatorTest extends \Codeception\Test\Unit
{
    /**
     * @var DataEnricher
     **/
    protected $enricher;

    /**
     * @var ActionInstantiator
     **/
    protected $actionInstantiator;

    /**
     * Do actions before each test
     */
    public function _before()
    {
        $this->enricher = new DataEnricher();
        $this->actionInstantiator = new ActionInstantiator($this->enricher);
    }

    /**
     * Test 'instantiate' method
     */
    public function testInstantiate()
    {
        $definitions = [
            $this->getAction('pass1', true),
            $this->getAction('not_pass1', false),
            $this->getAction('not_pass2', ['<eval>' => 'current.actor.name != null']),
            $this->getAction('pass2', ['<eval>' => 'actors.user != null']),
            $this->getAction('not_pass3', ['<eval>' => 'actors.non_exist != null']),
            $this->getAction('pass3', ['<eval>' => 'current.actor.key != null']),
            $this->getAction('pass4', ['<eval>' => 'current.actor.key == \'user\'']),
        ];

        $actions = [];
        foreach ($definitions as $definition) {
            $actions[] = clone $definition;
        }

        $process = $this->getProcess();
        $processOriginal = clone $process;

        $result = $this->actionInstantiator->instantiate($actions, $process);

        // Check that actions passed as parameter did not change
        foreach ($definitions as $key => $definition) {
            $this->assertEquals($definition, $actions[$key]);
        }

        // Check that process passed as parameter did not change
        $this->assertEquals($processOriginal, $process);

        $this->assertInstanceOf(AssocEntitySet::class, $result);
        $this->assertCount(4, $result);
        $this->assertTrue(isset($result['pass1']));
        $this->assertTrue(isset($result['pass2']));
        $this->assertTrue(isset($result['pass3']));
        $this->assertTrue(isset($result['pass4']));

        $this->assertCount(2, $result['pass1']->actors);
        $this->assertCount(2, $result['pass2']->actors);
        $this->assertCount(2, $result['pass3']->actors);
        $this->assertCount(1, $result['pass4']->actors);

        // Check that data enricher was applyed to action
        foreach ($result as $action) {
            $this->assertSame('Test bar action', $action->title);
        }
    }

    /**
     * Provide data for testing 'enrichAction' method
     *
     * @return array
     */
    public function enrichActionProvider()
    {
        return [
            [$this->getAction('pass1', true), true, 2],
            [$this->getAction('not_pass1', false), false, 2],
            [$this->getAction('not_pass2', ['<eval>' => 'current.actor.name != null']), false, 0],
            [$this->getAction('pass2', ['<eval>' => 'actors.user != null']), true, 2],
            [$this->getAction('not_pass3', ['<eval>' => 'actors.non_exist != null']), false, 2],
            [$this->getAction('pass3', ['<eval>' => 'current.actor.key != null']), true, 2],
            [$this->getAction('pass4', ['<eval>' => 'current.actor.key == \'user\'']), true, 1]
        ];
    }

    /**
     * Test 'enrichAction' method
     *
     * @dataProvider enrichActionProvider
     */
    public function testEnrichAction(Action $definition, $expectedCondition, $expectedActorsCount)
    {
        $action = clone $definition;

        $process = $this->getProcess();
        $processOriginal = clone $process;

        $result = $this->actionInstantiator->enrichAction($action, $process);

        // Check that action passed as parameter did not change
        $this->assertEquals($definition, $action);

        // Check that process passed as parameter did not change
        $this->assertEquals($processOriginal, $process);

        $this->assertInstanceOf(Action::class, $result);
        $this->assertSame('Test bar action', $result->title);
        $this->assertSame($expectedCondition, $result->condition);
        $this->assertCount($expectedActorsCount, $result->actors);
    }

    /**
     * Get Action object
     *
     * @param string $key
     * @param mixed $condition
     * @return Action
     */
    protected function getAction(string $key, $condition): Action
    {
        return (new Action)->setValues([
            'title' => [
                '<tpl>' => 'Test {{ assets.foo.scalar }} action'
            ],
            'key' => $key,
            'condition' => $condition,
            'actors' => ['user', 'system']  
        ]);
    }

    /**
     * Get process object
     *
     * @return Process
     */
    protected function getProcess(): Process
    {
        return (new Process)->setValues([
            'actors' => new AssocEntitySet([
                (new Actor)->setValues(['key' => 'user']),
                (new Actor)->setValues(['key' => 'system']),
            ]),
            'assets' => [
                'foo' => 'bar'
            ]
        ]);
    }
}
