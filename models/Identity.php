<?php declare(strict_types=1);

use Jasny\DB\Entity\Identifiable;
use LTO\Account;

/**
 * Identity entity
 */
class Identity extends MongoDocument implements Identifiable
{    
    /**
     * Unique identifier
     * @var string
     * @required
     */
    public $id;
    
    /**
     * Live contracts node the identity is using
     * @var string
     * @required
     */
    public $node;

    /**
     * Cryptographic (ED25519) public keys used in signing
     * @var string[]
     * @required
     */
    public $signkeys = [];
    
    /**
     * Cryptographic (X25519) public key used for encryption
     * @var string
     */
    public $encryptkey;
    
    /**
     * Get id property
     *
     * @return string
     */
    public static function getIdProperty(): string
    {
        return 'id';
    }


    /**
     * Describe the actor based on the known properties.
     *
     * @return string
     */
    public function describe(): string
    {
        return
            ($this->id !== null ? "identity '{$this->id}'" : null) ??
            ($this->signkeys !== null ? "signkey '" . reset($this->signkeys) . "'" : null) ??
            'unknown identity';
    }

    /**
     * See if the specified identity matches this actor.
     *
     * @param Identity $identity  Only some properties need to be set.
     * @return bool
     */
    public function matches(Identity $identity): bool
    {
        return
            ($identity->id !== null || $identity->signkeys !== []) && // Match at least one of these
            ($identity->id === null || $identity->id === $this->id) &&
            ($identity->signkeys === [] || array_contains($this->signkeys, $identity->signkeys, true));
    }

    /**
     * Create a (partial) identity from an LTO Account.
     *
     * @return Identity
     */
    public static function fromAccount(Account $account): Identity
    {
        $identity = new Identity();
        $identity->signkeys[] = $account->getPublicSignKey();

        return $identity;
    }
}
