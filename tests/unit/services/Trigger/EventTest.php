<?php

namespace Trigger;

use Jasny\TestHelper;
use Trigger\Event as EventTrigger;
use LTO;
use Psr\Container\ContainerInterface;

/**
 * @covers Trigger\Event
 */
class EventTest extends \Codeception\Test\Unit
{
    use TestHelper;

    public function actionProvider()
    {
        $action = (new \Action)->setValues([
            'chain' => 'abcdefg',
            'body' => ['foo' => 42],
        ]);

        $customAction = (new \Action)->setValues([
            'chain' => 'abcdefg',
            'answer' => 42,
        ]);

        return [
            [$action],
            [$customAction, '{chain:chain,body:{foo:answer}}', $action],
        ];
    }

    /**
     * @dataProvider actionProvider
     */
    public function test(\Action $action, ?string $projection = null, ?\Action $projectedAction = null)
    {
        $account = $this->createMock(LTO\Account::class);

        $signedEvent = $this->createMock(LTO\Event::class);

        $unsignedEvent = $this->createMock(LTO\Event::class);
        $unsignedEvent->expects($this->once())->method('signWith')
            ->with($this->identicalTo($account))
            ->willReturn($signedEvent);

        $chain = $this->createMock(LTO\EventChain::class);
        $chain->expects($this->any())->method('getLatestHash')->willReturn('1234567890');
        $chain->expects($this->any())->method('add')->with($this->identicalTo($signedEvent));

        $createEvent = $this->createCallbackMock($this->once(), [['foo' => 42], '1234567890'], $unsignedEvent);

        $repository = $this->createMock(\EventChainRepository::class);
        $repository->expects($this->once())->method('get')->with('abcdefg')->willReturn($chain);

        $jmespath = $projection === null
            ? $this->createCallbackMock($this->never())
            : $this->createCallbackMock($this->once(), [$projection, $this->identicalTo($action)], $projectedAction);

        $trigger = new EventTrigger($createEvent, $repository, $account, $jmespath);

        if ($projection !== null) {
            $container = $this->createMock(ContainerInterface::class);
            $container->expects($this->never())->method($this->anything());

            $trigger = $trigger->withConfig(['projection' => $projection], $container);
        }

        $trigger->apply($action);
    }


    public function badActionProvider()
    {
        return [
            [(new \Action)->setValues(['chain' => 'abcdefg']), 'body is unkown'],
            [(new \Action)->setValues(['body' => ['foo' => 42]]), 'chain is unkown'],
        ];
    }

    /**
     * @dataProvider badActionProvider
     *
     * @expectedException \UnexpectedValueException
     */
    public function testAssert(\Action $action, string $err)
    {
        $this->expectExceptionMessage('Unable to add an event: ' . $err);

        $account = $this->createMock(LTO\Account::class);

        $createEvent = $this->createCallbackMock($this->never());

        $repository = $this->createMock(\EventChainRepository::class);
        $repository->expects($this->never())->method('get');

        $jmespath = $this->createCallbackMock($this->never());

        $trigger = new EventTrigger($createEvent, $repository, $account, $jmespath);

        $trigger->apply($action);
    }
}
