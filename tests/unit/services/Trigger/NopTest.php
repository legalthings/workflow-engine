<?php

namespace Trigger;

use JmesPath\Env as JmesPath;
use Psr\Container\ContainerInterface;
use RuntimeException;

/**
 * @covers \Trigger\Nop
 * @covers \Trigger\AbstractTrigger
 */
class NopTest extends \Codeception\Test\Unit
{
    /**
     * @var \Trigger\Nop
     */
    protected $trigger;

    public function _before()
    {
        $jsonpath = JmesPath::createRuntime();
        $this->trigger = new Nop($jsonpath);
    }

    public function basicProvider()
    {
        return [
            ['trigger_response'],
            ['default_response'],
        ];
    }

    /**
     * @dataProvider basicProvider
     */
    public function testBasic(string $prop)
    {
        $process = new \Process();

        $action = \Action::fromData([
            $prop => 'yo',
        ]);

        $response = ($this->trigger)($process, $action);

        $this->assertInstanceOf(\Response::class, $response);
        $this->assertAttributeSame($action, 'action', $response);
        $this->assertAttributeEquals('yo', 'key', $response);
    }

    public function testDefaultResponse()
    {
        $process = new \Process();
        $action = new \Action();

        $response = ($this->trigger)($process, $action);

        $this->assertInstanceOf(\Response::class, $response);
        $this->assertAttributeSame($action, 'action', $response);
        $this->assertAttributeEquals('ok', 'key', $response);
    }

    public function testWithData()
    {
        $data = [
            'foo' => 'bar',
            'nmb' => 42,
        ];

        $process = new \Process();

        $action = \Action::fromData([
            'trigger_response' => 'ok',
            'data' => $data,
        ]);

        $response = ($this->trigger)($process, $action);

        $this->assertInstanceOf(\Response::class, $response);
        $this->assertAttributeSame($action, 'action', $response);

        $this->assertAttributeEquals($data, 'data', $response);
    }

    public function testWithConfig()
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->never())->method('get');

        $config = (object)['projection' => 'wop', 'foo' => 'bar'];
        $configuredTrigger = $this->trigger->withConfig($config, $container);
        $this->assertNotSame($this->trigger, $configuredTrigger);

        $sameTrigger = $configuredTrigger->withConfig(['projection' => 'wop'], $container);
        $this->assertSame($sameTrigger, $configuredTrigger);
    }

    public function testProject()
    {
        $container = $this->createMock(ContainerInterface::class);
        $trigger = $this->trigger->withConfig(['projection' => '{trigger_response: resp.key, data: info}'], $container);

        $data = [
            'foo' => 'bar',
            'nmb' => 42,
        ];

        $process = new \Process();

        $action = \Action::fromData([
            'resp' => [
                'key' => 'yo',
                'color' => 'red',
            ],
            'info' => $data,
        ]);

        $response = $trigger($process, $action);

        $this->assertInstanceOf(\Response::class, $response);
        $this->assertAttributeEquals('yo', 'key', $response);
        $this->assertAttributeEquals($data, 'data', $response);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage JMESPath projection failed: Syntax error at character 4
     */
    public function testProjectException()
    {
        $container = $this->createMock(ContainerInterface::class);
        $trigger = $this->trigger->withConfig(['projection' => '{bad'], $container);

        $process = new \Process();
        $action = new \Action();

        $trigger($process, $action);
    }
}
