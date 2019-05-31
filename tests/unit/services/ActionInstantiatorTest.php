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
     * Do actions before each test
     */
    public function _before()
    {
        $this->enricher = $this->createMock(DataEnricher::class);
    }

    /**
     * Test 'instantiate' method
     */
    public function testInstantiate()
    {
        $actors = [new Actor(), new Actor()];

        $actions = $this->getActions();
        $actionInstantiator = new ActionInstantiator($this->enricher);

        $this->enricher->expects($this->exactly(4))->method('applyTo')
            ->will($this->returnCallback(function(Action $action) {
                if ($action->key === 'zoo' || $action->key === 'baz') {
                    $action->condition = true;
                } elseif ($action->key === 'zoo2' || $action->key === 'baz2') {
                    $action->condition = false;
                }
            }));

        $process = $this->createMock(Process::class);
        $process->expects($this->exactly(2))->method('getActor')
            ->withConsecutive(['default'], ['system'])
            ->willReturnOnConsecutiveCalls($actors[0], $actors[1]);

        $result = $actionInstantiator->instantiate($actions, $process);

        $this->assertInstanceOf(AssocEntitySet::class, $result);
        $this->assertCount(5, $result);
        $this->assertTrue(isset($result['foo']));
        $this->assertTrue(isset($result['bar']));
        $this->assertTrue(isset($result['zoo']));
        $this->assertTrue(isset($result['baz']));
        $this->assertTrue(isset($result['baz2']));

        $this->assertTrue($result['zoo']->condition);
        $this->assertTrue($result['baz']->condition);
        $this->assertFalse($result['baz2']->condition);
    }

    /**
     * Get test actions
     *
     * @return array
     */
    protected function getActions()
    {
        return [
            (new Action)->setValues([
                'key' => 'foo',
                'condition' => true,
                'actors' => ['user']
            ]),
            (new Action)->setValues([
                'key' => 'bar',
                'condition' => false,
                'actors' => ['user']
            ]),
            (new Action)->setValues([
                'key' => 'foo2',
                'condition' => true,
                'actors' => []
            ]),
            (new Action)->setValues([
                'key' => 'bar2',
                'condition' => false,
                'actors' => []
            ]),
            (new Action)->setValues([
                'key' => 'zoo',
                'condition' => DataInstruction::fromData(['<eval>' => 'true && current.actor == null']),
                'actors' => ['default'] 
            ]),
            (new Action)->setValues([
                'key' => 'zoo2',
                'condition' => DataInstruction::fromData(['<eval>' => 'true && current.actor == \'rest\'']),
                'actors' => ['system'] 
            ]),
            (new Action)->setValues([
                'key' => 'baz',
                'condition' => DataInstruction::fromData(['<eval>' => 'foo.bar == \'test\'']),
                'actors' => ['user'] 
            ]),
            (new Action)->setValues([
                'key' => 'baz2',
                'condition' => DataInstruction::fromData(['<eval>' => 'foo.bar == \'rest\'']),
                'actors' => ['user'] 
            ]),
        ];
    }
}
