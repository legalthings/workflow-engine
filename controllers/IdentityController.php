<?php

declare(strict_types=1);

/**
 * Identity controller.
 */
class IdentityController extends BaseController
{
    /**
     * @param LTO\Account     $node
     * @param IdentityGateway $identities
     */
    public function __construct(LTO\Account $node, IdentityGateway $identities)
    {
        object_init($this, get_defined_vars());
    }

    /**
     * Executed before each action.
     *
     * @throws AuthException
     */
    public function before()
    {
        $this->authz(Identity::AUTHZ_ADMIN, "Signing identity isn't allowed to manage identities");
    }

    /**
     * Add or update identity
     * @throws Jasny\ValidationException
     */
    public function putAction(): void
    {
        $input = $this->getInput();
        $identity = $this->identities->create();

        $identity->setValues($input);
        $identity->validate()->mustSucceed();

        $this->identities->save($identity, ['existing' => 'replace']);

        $this->output($identity);
    }

    /**
     * Get an identity.
     *
     * @param string $id Identity id
     * @throws EntityNotFoundException
     */
    public function getAction(string $id): void
    {
        $identity = $this->identities->fetch($id);

        $this->output($identity);
    }

    /**
     * Delete identity
     *
     * @param string $id
     * @throws EntityNotFoundException
     */
    public function deleteAction(string $id): void
    {
        $identity = $this->identities->fetch($id);

        $this->identities->delete($identity);
    }
}
