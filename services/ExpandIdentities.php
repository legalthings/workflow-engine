<?php declare(strict_types=1);

use Jasny\DB\Entity\Identifiable;

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
     * Invoke this event handler.
     *
     * @param Process $process
     */
    public function __invoke(Process $process): void
    {
        foreach ($process->actors as $actor) {
            $actor->identity = $this->expand($actor->identity);
        }
    }

    /**
     * Expand the property.
     *
     * @param Identity|string $identity
     * @return Identity
     */
    protected function expand($identity): Identity
    {
        if ($identity instanceof Identity && !$identity->isGhost()) {
            return $identity;
        }

        $id = $identity instanceof Identity ? $identity->getId() : $identity;

        try {
            return $this->gateway->fetch($id);
        } catch (EntityNotFoundException $exception) {
            trigger_error($exception, E_USER_WARNING);

            return $identity instanceof Identity ? $identity : Identity::lazyload(['id' => $identity]);
        }
    }
}
