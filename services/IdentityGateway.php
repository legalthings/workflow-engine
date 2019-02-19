<?php declare(strict_types=1);

use Improved as i;
use Jasny\DB\Entity;
use Jasny\DB\EntitySet;
use Jasny\EventDispatcher\EventDispatcher;

/**
 * Stub DI while using static methods.
 * This will be fixed with the new Jasny DB abstraction layer.
 *
 * @codeCoverageIgnore
 */
class IdentityGateway implements Gateway
{
    /**
     * Create an identity.
     *
     * @return Identity
     */
    public function create(): Identity
    {
        return new Identity();
    }

    /**
     * Fetch an identity.
     *
     * @param string|array $id  ID or filter
     * @param array        $opts
     * @return Identity|null
     */
    public function fetch($id, array $opts = []): ?Identity
    {
        return Identity::fetch($id, $opts);
    }

    /**
     * Check if an identity exists.
     *
     * @param string|array $id  ID or filter
     * @param array        $opts
     * @return bool
     */
    public function exists($id, array $opts = []): bool
    {
        return Identity::exists($id, $opts);
    }

    /**
     * Fetch all identities.
     *
     * @param array     $filter
     * @param array     $sort
     * @param int|array $limit  Limit or [limit, offset]
     * @param array     $opts
     * @return IdentitySet
     */
    public function fetchAll(array $filter = [], $sort = [], $limit = null, array $opts = []): IdentitySet
    {
        /** @var EntitySet $set */
        $set = Identity::fetchAll($filter, $sort, $limit, $opts);

        return $set;
    }

    /**
     * Count all identities in the collection
     *
     * @param array $filter
     * @param array $opts
     * @return int
     */
    public function count(array $filter = [], array $opts = []): int
    {
        return Identity::count($filter, $opts);
    }


    /**
     * Add or update the entity to the DB.
     *
     * @param Identity $entity
     * @param array $opts
     */
    public function save(Entity $entity, array $opts = []): void
    {
        i\type_check($entity, Identity::class);

        $entity->save($opts);
    }

    /**
     * Delete the entity from the DB.
     *
     * @param Identity $entity
     * @param array $opts
     */
    public function delete(Entity $entity, array $opts = []): void
    {
        i\type_check($entity, Identity::class);

        $entity->delete($opts);
    }
}
