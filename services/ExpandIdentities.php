<?php declare(strict_types=1);

/**
 * Expand identities on fetch event of a process.
 * To be used with the event dispatcher.
 */
class ExpandIdentities
{
    /**
     * @var IdentityGateway
     */
    protected $gateway;

    /**
     * Return a copy for a specific gateway
     *
     * @param IdentityGateway $gateway
     * @return self
     */
    public function __construct(IdentityGateway $gateway)
    {
        $this->gateway = $gateway;
    }

    /**
     * Get the identity gateway.
     */
    public function getGateway(): IdentityGateway
    {
        return $this->gateway;
    }

    /**
     * Invoke this event handler.
     *
     * @param Process $process
     */
    public function __invoke(Process $process): void
    {
        foreach ($process->actors as $actor) {
            if (!isset($actor->identity)) {
                continue;
            }

            $actor->identity = $this->expand($actor->identity);
        }
    }

    /**
     * Expand the property.
     *
     * @param Identity|string|null $identity
     * @return Identity|null
     */
    protected function expand($identity): ?Identity
    {
        if (!isset($identity)) {
            return null;
        }

        if ($identity instanceof Identity && !$identity->isGhost()) {
            return $identity;
        }

        $id = $identity instanceof Identity ? $identity->getId() : $identity;

        try {
            return $this->gateway->fetch($id);
        } catch (EntityNotFoundException $exception) {
            return $identity instanceof Identity ? $identity : Identity::lazyload(['id' => $identity]);
        }
    }
}
