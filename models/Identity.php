<?php declare(strict_types=1);

use Jasny\DB\Entity\Identifiable;

/**
 * Identity entity
 */
class Identity extends MongoDocument implements Identifiable
{    
    /**
     * @var string
     */
    public $schema = 'https://specs.livecontracts.io/v0.2.0/identity/schema.json#';
    
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
     * @var array
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
}
