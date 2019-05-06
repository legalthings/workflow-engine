<?php

namespace Trigger;

use Jasny\TestHelper;
use Trigger\Event as EventTrigger;
use Psr\Container\ContainerInterface;
use LegalThings\DataEnricher;
use LTO\Event;
use LTO\Account;
use LTO\EventChain;
use PHPUnit\Framework\MockObject\Builder\InvocationMocker;

/**
 * @covers Trigger\Event
 */
class EventTest extends \Codeception\Test\Unit
{
    use TestHelper;

    public function applySingleEventProvider()
    {
        $action = (new \Action)->setValues([
            'body' => ['foo' => 42],
            'process' => (object)['chain' => 'abcdefg']
        ]);

        $customAction = (new \Action)->setValues([
            'answer' => 42,
            'process' => (object)['chain' => 'abcdefg']
        ]);

        return [
            [$action],
            [$customAction, '{chain:chain,body:{foo:answer}}', $action],
        ];
    }

    /**
     * Test creating single event
     * 
     * @dataProvider applySingleEventProvider
     */
    public function testApplySingleEvent(\Action $action, ?string $projection = null, ?\Action $projectedAction = null)
    {
        $account = $this->createMock(Account::class);

        $signedEvent = $this->createMock(Event::class);

        $unsignedEvent = $this->createMock(Event::class);
        $unsignedEvent->expects($this->once())->method('signWith')
            ->with($this->identicalTo($account))
            ->willReturn($signedEvent);

        $chain = $this->createMock(EventChain::class);
        $chain->expects($this->any())->method('getLatestHash')->willReturn('1234567890');
        $chain->expects($this->any())->method('add')->with($this->identicalTo($signedEvent));

        $createEvent = $this->createCallbackMock($this->once(), [(object)['foo' => 42], '1234567890'], $unsignedEvent);

        $repository = $this->createMock(\EventChainRepository::class);
        $repository->expects($this->once())->method('get')->with('abcdefg')->willReturn($chain);

        $jmespath = $projection === null
            ? $this->createCallbackMock($this->never())
            : $this->createCallbackMock($this->once(), [$projection, $this->identicalTo($action)], $projectedAction);

        $enricher = $this->createMock(DataEnricher::class);

        $trigger = new EventTrigger($createEvent, $repository, $account, $jmespath, $enricher);

        if ($projection !== null) {
            $container = $this->createMock(ContainerInterface::class);
            $container->expects($this->never())->method($this->anything());

            $trigger = $trigger->withConfig(['projection' => $projection], $container);
        }

        $trigger->apply($action);
    }

    /**
     * Test 'apply' method, when creating multiple events
     */
    public function testApplyMultipleEvents()
    {
        $action = (new \Action)->setValues([
            'body' => [['foo' => 42], ['bar' => 43]],
            'process' => (object)['chain' => 'abcdefg']
        ]);

        $account = $this->createMock(Account::class);

        $signedEvents = [
            $this->createMock(Event::class),
            $this->createMock(Event::class)
        ];

        $unsignedEvents = [
            $this->createMock(Event::class),
            $this->createMock(Event::class)
        ];

        $unsignedEvents[0]->expects($this->once())->method('signWith')
            ->with($this->identicalTo($account))
            ->willReturn($signedEvents[0]);
        $unsignedEvents[1]->expects($this->once())->method('signWith')
            ->with($this->identicalTo($account))
            ->willReturn($signedEvents[1]);

        $chain = $this->createMock(EventChain::class);
        $chain->expects($this->exactly(2))->method('getLatestHash')
            ->willReturnOnConsecutiveCalls('1234567890', 'prev_event_hash');
        $chain->expects($this->exactly(2))->method('add')->withConsecutive(
            [$this->identicalTo($signedEvents[0])],
            [$this->identicalTo($signedEvents[1])]
        );

        $createEvent = $this->createCallbackMock(
            $this->exactly(2), 
            function(InvocationMocker $invoke) use ($unsignedEvents) {
                $invoke->withConsecutive(
                    [(object)['foo' => 42], '1234567890'],
                    [(object)['bar' => 43], 'prev_event_hash']
                )->willReturnOnConsecutiveCalls($unsignedEvents[0], $unsignedEvents[1]);
            }
        );

        $repository = $this->createMock(\EventChainRepository::class);
        $repository->expects($this->once())->method('get')->with('abcdefg')->willReturn($chain);

        $jmespath = $this->createCallbackMock($this->never());
        $enricher = $this->createMock(DataEnricher::class);

        $trigger = new EventTrigger($createEvent, $repository, $account, $jmespath, $enricher);
        $trigger->apply($action);
    }


    public function badActionProvider()
    {
        return [
            [(new \Action)->setValues(['process' => (object)['chain' => 'abcdefg']]), 'body is unkown'],
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

        $account = $this->createMock(Account::class);

        $createEvent = $this->createCallbackMock($this->never());

        $repository = $this->createMock(\EventChainRepository::class);
        $repository->expects($this->never())->method('get');

        $jmespath = $this->createCallbackMock($this->never());
        $enricher = $this->createMock(DataEnricher::class);

        $trigger = new EventTrigger($createEvent, $repository, $account, $jmespath, $enricher);

        $trigger->apply($action);
    }
}
