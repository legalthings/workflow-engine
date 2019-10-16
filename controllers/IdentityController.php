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
        $this->authz(Identity::AUTHZ_USER, "Signing identity isn't allowed to manage identities");
    }

    /**
     * Add or update identity
     * @throws Jasny\ValidationException
     * @throws AuthException
     */
    public function putAction(): void
    {
        $input = $this->getInput()
            + get_class_vars(Identity::class); // Replace instead of update

        $identity = $this->identities->fetchOrCreate($input['id'] ?? null);
        $oldAuthz = $identity->authz;

        $identity->setValues($input);

        if ($identity->authz > Identity::AUTHZ_PARTICIPANT || $oldAuthz > Identity::AUTHZ_PARTICIPANT) {
            $this->authz(Identity::AUTHZ_ADMIN, "Signing identity is only allowed to manage participant identities");
        }

        $identity->validate()->mustSucceed();
        $this->identities->save($identity);

        $this->output($identity);
    }

    /**
     * Get an identity.
     *
     * @param string $id Identity id
     * @throws EntityNotFoundException
     * @throws AuthException
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
     * @throws AuthException
     */
    public function deleteAction(string $id): void
    {
        $this->authz(Identity::AUTHZ_ADMIN, "Signing identity isn't allowed to remove identities");

        $identity = $this->identities->fetch($id);
        $this->identities->delete($identity);

        $this->noContent();
    }
}
