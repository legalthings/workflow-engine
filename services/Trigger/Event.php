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
     * Account to sign new events (our account).
     * @var LTO\Account
     */
    protected $account;

    /**
     * Event trigger constructor.
     *
     * @param EventChainRepository $repository
     * @param LTO\Account          $account
     * @param callable             $jmespath    "jmespath"
     */
    public function __construct(EventChainRepository $repository, LTO\Account $account, callable $jmespath)
    {
        $this->repository = $repository;
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
        $this->assert($info);

        $chain = $this->repository->get($info->chain);

        $newEvent = (new LTO\Event($info->body, $chain->getLatestHash()))->signWith($this->account);
        $chain->add($newEvent);

        $this->repository->update($chain);

        return new \Response();
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
}
