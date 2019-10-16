<?php

namespace Trigger;

use EventChainRepository;
use LTO;

/**
 * Create and add an event to the event chain.
 */
class Event extends AbstractTrigger
{
    /**
     * @var EventChainRepository
     */
    protected $repository;

    /**
     * @var callable
     */
    protected $createEvent;

    /**
     * Account to sign new events (our account).
     * @var LTO\Account
     */
    protected $account;

    /**
     * Event trigger constructor.
     *
     * @param callable             $createEvent
     * @param EventChainRepository $repository
     * @param LTO\Account          $account
     * @param callable             $jmespath     "jmespath"
     */
    public function __construct(
        callable $createEvent,
        EventChainRepository $repository,
        LTO\Account $account,
        callable $jmespath
    ) {
        $this->repository = $repository;
        $this->createEvent = $createEvent;
        $this->account = $account;

        parent::__construct($jmespath);
    }

    /**
     * Invoke for an action.
     *
     * @param \Action $action
     * @return \Response|null
     */
    public function apply(\Action $action): ?\Response
    {
        $info = $this->project($action);
        $info->chain = $action->process->chain ?? null;
        $this->assert($info);

        // data enricher requires objects, not arrays
        $info->body = json_decode(json_encode($info->body));

        $events = $this->createEvents($info->body, $info->chain);

        return $this->createResponse($events);
    }

    /**
     * Create events from action data
     *
     * @param \stdClass|array $eventsData
     * @param string $chainId 
     * @return array
     */
    protected function createEvents($eventsData, string $chainId): array
    {
        $chain = $this->repository->get($chainId);

        if (!is_array($eventsData)) {
            $eventsData = [$eventsData];
        }

        $events = [];
        foreach ($eventsData as $body) {
            $event = ($this->createEvent)($body, $chain->getLatestHash())->signWith($this->account);
            $chain->add($event);

            $events[] = $event;
        }

        $this->repository->update($chain);

        return $events;
    }

    /**
     * Assert projected info.
     *
     * @param \stdClass $info
     * @throws \UnexpectedValueException
     */
    protected function assert(\stdClass $info): void
    {
        if (!isset($info->chain)) {
            throw new \UnexpectedValueException('Unable to add an event: chain is unkown');
        }

        if (!isset($info->body)) {
            throw new \UnexpectedValueException('Unable to add an event: body is unkown');
        }
    }

    /**
     * Create response for an event.
     *
     * @param array $events
     * @return \Response
     */
    protected function createResponse(array $events): \Response
    {
        return (new \Response)->setValues(['data' => $events, 'key' => 'ok']);
    }
}
