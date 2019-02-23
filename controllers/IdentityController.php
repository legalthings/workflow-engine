<?php declare(strict_types=1);

/**
 * Identity controller.
 */
class IdentityController extends BaseController
{
    /**
     * @var IdentityGateway
     */
    protected $identities;

    /**
     * @param IdentityGateway $identities
     */
    public function __construct(IdentityGateway $identities)
    {
        $this->identities = $identities;
    }

    /**
     * Add an identity
     */
    public function addAction(): void
    {
        $identity = $this->identities->create()->setValues($this->getInput());
        $identity->validate()->mustSucceed();

        $this->identities->save($identity);

        $this->output($identity);
    }

    /**
     * Get an identity.
     *
     * @param string $id  Identity id
     */
    public function getAction(string $id): void
    {
        $identity = $this->identities->fetch($id);

        $this->output($identity);
    }

    /**
     * Update identity.
     *
     * @param string $id  Identity id
     */
    public function updateAction(string $id): void
    {
        $identity = $this->identities->fetch($id);

        $identity->setValues($this->getInput());
        $identity->validate()->mustSucceed();

        $this->identities->save($identity);

        $this->output($identity);
    }

    /**
     * Delete identity
     *
     * @param string $id
     */
    public function deleteAction(string $id): void
    {
        $identity = $this->identities->fetch($id);

        $this->identities->delete($identity);
    }
}
