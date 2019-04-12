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
        $info->chain = $action->process->chain;

        $this->assert($info);

        $chain = $this->repository->get($info->chain);

        $newEvent = ($this->createEvent)($info->body, $chain->getLatestHash())->signWith($this->account);
        $chain->add($newEvent);

        $this->repository->update($chain);

        return $this->createResponse($newEvent);
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
     * @param LTO\Event $event
     * @return \Response
     */
    protected function createResponse(LTO\Event $event): \Response
    {
        return (new \Response)->setValues(['data' => $event, 'key' => 'ok']);
    }
}
