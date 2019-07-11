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
class ScenarioGateway implements Gateway
{
    /**
     * @var EventDispatcher
     */
    protected $dispatcher;

    /**
     * Class constructor
     *
     * @param EventDispatcher $dispatcher  "scenario_events"
     */
    public function __construct(EventDispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * Create an scenario.
     *
     * @return Scenario
     */
    public function create(): Scenario
    {
        $scenario = new Scenario();
        $scenario->setDispatcher($this->dispatcher);

        return $scenario;
    }

    /**
     * Fetch an scenario.
     *
     * @param string|array $id  ID or filter
     * @param array        $opts
     * @return Scenario
     * @throws EntityNotFoundException
     */
    public function fetch($id, array $opts = []): Scenario
    {
        $scenario = Scenario::fetch($id, $opts);

        if ($scenario === null) {
            throw new EntityNotFoundException("Scenario not found");
        }

        $scenario->setDispatcher($this->dispatcher);

        return $scenario;
    }

    /**
     * Check if an scenario exists.
     *
     * @param string|array $id  ID or filter
     * @param array        $opts
     * @return bool
     */
    public function exists($id, array $opts = []): bool
    {
        return Scenario::exists($id, $opts);
    }

    /**
     * Fetch all scenarios.
     *
     * @param array     $filter
     * @param array     $sort
     * @param int|array $limit  Limit or [limit, offset]
     * @param array     $opts
     * @return EntitySet&iterable<Scenario>
     */
    public function fetchAll(array $filter = [], $sort = [], $limit = null, array $opts = []): EntitySet
    {
        /** @var EntitySet&iterable<Scenario> $set */
        $set = Scenario::fetchAll($filter, $sort, $limit, $opts);

        foreach ($set as $scenario) {
            $scenario->setDispatcher($this->dispatcher);
        }

        return $set;
    }

    /**
     * Count all scenarios in the collection
     *
     * @param array $filter
     * @param array $opts
     * @return int
     */
    public function count(array $filter = [], array $opts = []): int
    {
        return Scenario::count($filter, $opts);
    }


    /**
     * Add or update the entity to the DB.
     *
     * @param Scenario $entity
     * @param array $opts
     */
    public function save(Entity $entity, array $opts = []): void
    {
        i\type_check($entity, Scenario::class);

        $entity->save($opts);
    }

    /**
     * Delete the entity from the DB.
     *
     * @param Scenario $entity
     * @param array $opts
     */
    public function delete(Entity $entity, array $opts = []): void
    {
        i\type_check($entity, Scenario::class);

        $entity->delete($opts);
    }
}
