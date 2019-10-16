<?php

declare(strict_types=1);

use Jasny\DB\Entity\Identifiable;
use LTO\Account;

/**
 * Identity entity
 */
class Identity extends MongoDocument implements Identifiable
{
    public const AUTHZ_PARTICIPANT = 0;
    public const AUTHZ_USER = 1;
    public const AUTHZ_ADMIN = 10;

    /**
     * Unique identifier
     * @var string
     * @required
     */
    public $id;
    
    /**
     * LTO Network node the identity is using
     * @var string|null
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
     * Authorization level.
     * @var int
     */
    public $authz = self::AUTHZ_PARTICIPANT;


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
     * Describe the identity based on the known properties.
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
     * Set the values of the Identity
     *
     * @param array|object $values
     * @return $this
     */
    public function setValues($values)
    {
        $values = (array)$values;

        if (isset($values['authz']) && is_string($values['authz'])) {
            $values['authz'] = value_from_const(__CLASS__ . '::AUTHZ_%s', $values['authz']);
        }

        parent::setValues($values);

        return $this;
    }

    /**
     * See if the specified identity matches this identity.
     *
     * @param Identity $identity  Only some properties need to be set.
     * @return bool
     */
    public function matches(Identity $identity): bool
    {
        return
            ($identity->id !== null || $identity->signkeys !== []) && // Match at least one of these
            ($identity->id === null || $identity->id === $this->id) &&
            ($identity->signkeys === [] || array_contains((array)$this->signkeys, $identity->signkeys, true));
    }

    /**
     * Create a (partial) identity from an LTO Account.
     *
     * @return Identity
     */
    public static function fromAccount(Account $account): Identity
    {
        $identity = new Identity();
        $identity->signkeys['system'] = $account->getPublicSignKey(); // TODO: This doesn't seem right. We don't know which key has been used

        return $identity;
    }

    /**
     * @return object
     */
    public function jsonSerialize()
    {
        $object = parent::jsonSerialize();
        $object->authz = [0 => 'participant', 1 => 'user', 10 => 'admin'][$object->authz];

        return $object;
    }
}
