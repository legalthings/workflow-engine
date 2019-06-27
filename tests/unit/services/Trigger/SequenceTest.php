<?php

namespace Trigger;

use JmesPath\Env as JmesPath;
use Psr\Container\ContainerInterface;
use RuntimeException;
use Process;
use Action;
use Response;

/**
 * @covers \Trigger\Sequence
 * @covers \Trigger\AbstractTrigger
 */
class SequenceTest extends \Codeception\Test\Unit
{
    use \Jasny\TestHelper;

    /**
     * Provide data for testing 'withConfig' method
     *
     * @return array
     */
    public function withConfigProvider()
    {
        return [
            [true],
            [false]
        ];
    }

    /**
     * Test 'withConfig' method
     *
     * @dataProvider withConfigProvider
     */
    public function testWithConfig($isObject)
    {
        $settings = [
            'triggers' => [
                (object)['type' => 'event', 'foo' => 'bar'],
                (object)['type' => 'http', 'zoo' => 'baz'],
                (object)['data' => 'test']
            ]
        ];       

        if ($isObject) {
            $settings = (object)$settings;
        }

        $triggers = [
            $this->getMockForAbstractClass(AbstractTrigger::class, [], '', false, true, true, ['withConfig']),
            $this->getMockForAbstractClass(AbstractTrigger::class, [], '', false, true, true, ['withConfig']),
            $this->getMockForAbstractClass(AbstractTrigger::class, [], '', false, true, true, ['withConfig'])
        ];

        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->exactly(3))->method('get')->withConsecutive(
            ['event_trigger'],
            ['http_trigger'],
            ['unknown_trigger']
        )->willReturnOnConsecutiveCalls($triggers[0], $triggers[1], $triggers[2]);

        $this->expectWithConfig($triggers, $settings, $container, 0);
        $this->expectWithConfig($triggers, $settings, $container, 1);
        $this->expectWithConfig($triggers, $settings, $container, 2);

        $sequence = new Sequence();
        $result = $sequence->withConfig($settings, $container);
        $sequenceTriggers = $this->getPrivateProperty($sequence, 'triggers');
        $resultTriggers = $this->getPrivateProperty($result, 'triggers');

        $this->assertInstanceOf(Sequence::class, $result);
        $this->assertNotSame($sequence, $result);
        $this->assertCount(0, $sequenceTriggers);
        $this->assertCount(3, $resultTriggers);

        $this->assertEquals($triggers[0], $resultTriggers[0]);
        $this->assertEquals($triggers[1], $resultTriggers[1]);
        $this->assertEquals($triggers[2], $resultTriggers[2]);

        $this->assertNotSame($triggers[0], $resultTriggers[0]);
        $this->assertNotSame($triggers[1], $resultTriggers[1]);
        $this->assertNotSame($triggers[2], $resultTriggers[2]);
    }

    /**
     * Set expectation for init triggers
     *
     * @param array $triggers
     * @param array $settings 
     * @param ContainerInterface $container 
     * @param int $idx 
     */
    protected function expectWithConfig($triggers, $settings, $container, $idx)
    {
        $itemSettings = is_array($settings) ? $settings['triggers'][$idx] : $settings->triggers[$idx];

        $triggers[$idx]->expects($this->once())->method('withConfig')
            ->with($itemSettings, $this->identicalTo($container))->will($this->returnCallback(
                function($itemSettingsArg) use ($triggers, $idx) {
                    $clone = clone $triggers[$idx];
                    return $clone;
                }
            ));
    }

    /**
     * Get private property of object
     *
     * @param object $object
     * @param string $name 
     * @return mixed
     */
    protected function getPrivateProperty($object, $name)
    {        
        $refl = new \ReflectionObject($object);
        $property = $refl->getProperty($name);
        $property->setAccessible(true);

        return $property->getValue($object);
    }

    /**
     * Test 'withConfig' method, if triggers settings are not set
     *
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Sequence trigger config should have 'triggers' setting
     */
    public function testWithConfigNoTriggers()
    {
        $container = $this->createMock(ContainerInterface::class);

        $sequence = new Sequence();
        $sequence->withConfig([], $container);
    }

    /**
     * Test '__invoke' method
     */
    public function testInvoke()
    {
        $process = $this->createMock(Process::class);
        $action = $this->createMock(Action::class);
        $action->default_response = 'foo_response';

        $sequence = new Sequence();

        $triggers = [
            $this->createTriggerCallbackMock($process, $action, '1'),
            $this->createTriggerCallbackMock($process, $action, '.2'),
            $this->createTriggerCallbackMock($process, $action, '.3')
        ];

        $this->setPrivateProperty($sequence, 'triggers', $triggers);

        $result = $sequence($process, $action);

        $this->assertInstanceOf(Response::class, $result);
        $this->assertSame('1.2.3', $result->_triggers);
    }

    /**
     * Mock trigger
     *
     * @param Process $process
     * @param Action $action 
     * @param string $add 
     * @return callable
     */
    protected function createTriggerCallbackMock($process, $action, $add)
    {
        $returnCallback = function($processArg, $action) use ($process, $add) {
            $this->assertSame($process, $processArg);
            $this->assertInstanceOf(Response::class, $action->previous_response);                    
            $this->assertSame('foo_response', $action->previous_response->key);

            if (!isset($action->previous_response->_triggers)) {
                $action->previous_response->_triggers = '';
            }
            $action->previous_response->_triggers .= $add;

            return $action->previous_response;
        };

        return $this->createCallbackMock($this->once(), function($invoke) use ($returnCallback) {
            $invoke->will($this->returnCallback($returnCallback));
        });
    }
}
