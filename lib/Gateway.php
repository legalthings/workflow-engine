<?php declare(strict_types=1);

use Jasny\DB\Entity;
use Jasny\DB\EntitySet;

/**
 * Basic data gateway.
 * @deprecated To be replaced with the new Jasny DB layer.
 */
interface Gateway
{
    /**
     * Create an event chain.
     *
     * @return Entity
     */
    public function create();

    /**
     * Fetch an entity.
     *
     * @param string|array $id  ID or filter
     * @param array        $opts
     * @return Entity
     * @throws EntityNotFoundException
     */
    public function fetch($id, array $opts = []);

    /**
     * Check if an entity exists.
     *
     * @param string|array $id  ID or filter
     * @param array        $opts
     * @return bool
     */
    public function exists($id, array $opts = []): bool;

    /**
     * Fetch all entities.
     *
     * @param array     $filter
     * @param array     $sort
     * @param int|array $limit  Limit or [limit, offset]
     * @param array     $opts
     * @return EntitySet&iterable<Entity>
     */
    public function fetchAll(array $filter = [], $sort = [], $limit = null, array $opts = []);

    /**
     * Fetch all entities as data (no ORM).
     *
     * @param array     $filter
     * @param array     $sort
     * @param int|array $limit  Limit or [limit, offset]
     * @param array     $opts
     * @return array
     */
    public function fetchList(array $filter = [], $sort = [], $limit = null, array $opts = []): array;

    /**
     * Count all entities in the collection
     *
     * @param array $filter
     * @param array $opts
     * @return int
     */
    public function count(array $filter = [], array $opts = []): int;


    /**
     * Add or update the entity to the DB.
     *
     * @param Entity $entity
     * @param array $opts
     */
    public function save(Entity $entity, array $opts = []): void;

    /**
     * Delete the entity from the DB.
     *
     * @param Entity $entity
     * @param array $opts
     */
    public function delete(Entity $entity, array $opts = []): void;
}
