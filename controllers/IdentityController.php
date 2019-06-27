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
     * Add or update identity
     */
    public function putAction(): void
    {
        $data = $this->getInput();
        $identity = $this->identities->create()->setValues($data);

        try {
            $existing = $this->identities->fetch($data['id']);       
        } catch (EntityNotFoundException $e) {
            $existing = null;
        };

        if (isset($existing)) {
            $identity = $existing->setValues($identity->getValues());
        }

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
