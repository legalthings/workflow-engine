<?php

declare(strict_types=1);

use Improved as i;
use Jasny\DB\Entity;
use Jasny\DB\EntitySet;

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
     * @return Identity
     */
    public function fetch($id, array $opts = []): Identity
    {
        $identity = Identity::fetch($id, $opts);

        if ($identity === null) {
            throw new EntityNotFoundException("Identity not found");
        }

        return $identity;
    }

    /**
     * Fetch an identity or create a new one if it can't be found.
     *
     * @param string|array $id  ID or filter
     * @param array        $opts
     * @return Identity
     */
    public function fetchOrCreate($id, array $opts = []): Identity
    {
        $identity = $id !== null ? Identity::fetch($id, $opts) : null;

        return $identity ?? $this->create()->setValues(['id' => $id]);
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
     * @return EntitySet
     */
    public function fetchAll(array $filter = [], $sort = [], $limit = null, array $opts = []): EntitySet
    {
        return Identity::fetchAll($filter, $sort, $limit, $opts);
    }

    /**
     * Fetch all identities as data (no ORM).
     *
     * @param array     $filter
     * @param array     $sort
     * @param int|array $limit  Limit or [limit, offset]
     * @param array     $opts
     * @return array
     */
    public function fetchList(array $filter = [], $sort = [], $limit = null, array $opts = []): array
    {
        return Identity::fetchList($filter, $sort, $limit, $opts);
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
