<?php

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
class ProcessGateway implements Gateway
{
    /**
     * @var EventDispatcher
     */
    protected $dispatcher;

    /**
     * Class constructor
     *
     * @param EventDispatcher $dispatcher  "process_events"
     */
    public function __construct(EventDispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher
            ->on('update', [$this, 'autosave'])
            ->on('step', [$this, 'autosave']);
    }

    /**
     * Disable saving the process on update and step events.
     */
    public function disableAutosave(): void
    {
        $this->dispatcher = $this->dispatcher
            ->off('update', [$this, 'autosave'])
            ->off('step', [$this, 'autosave']);
    }

    /**
     * Create process.
     *
     * @return Process
     */
    public function create(): Process
    {
        /** @var Process $process */
        $process = new Process();
        $process->setDispatcher($this->dispatcher);

        return $process;
    }

    /**
     * Fetch process.
     *
     * @param string|array $id  ID or filter
     * @param array        $opts
     * @return Process
     * @throws EntityNotFoundException
     */
    public function fetch($id, array $opts = []): ?Process
    {
        $process = Process::fetch($id, $opts);

        if ($process === null) {
            throw new EntityNotFoundException("Process not found");
        }

        $process->setDispatcher($this->dispatcher);
        $process->dispatch('fetch');

        return $process;
    }

    /**
     * Check if process exists.
     *
     * @param string|array $id  ID or filter
     * @param array        $opts
     * @return bool
     */
    public function exists($id, array $opts = []): bool
    {
        return Process::exists($id, $opts);
    }

    /**
     * Fetch all processes.
     *
     * @param array     $filter
     * @param array     $sort
     * @param int|array $limit  Limit or [limit, offset]
     * @param array     $opts
     * @return EntitySet&iterable<Process>
     */
    public function fetchAll(array $filter = [], $sort = [], $limit = null, array $opts = []): EntitySet
    {
        /** @var EntitySet&iterable<Process> $set */
        $set = Process::fetchAll($filter, $sort, $limit, $opts);

        foreach ($set as $process) {
            $process->setDispatcher($this->dispatcher);
            $process->dispatch('fetch');
        }

        return $set;
    }

    /**
     * Count all processes in the collection
     *
     * @param array $filter
     * @param array $opts
     * @return int
     */
    public function count(array $filter = [], array $opts = []): int
    {
        return Process::count($filter, $opts);
    }


    /**
     * Add or update the entity to the DB.
     *
     * @param Process $entity
     * @param array $opts
     */
    public function save(Entity $entity, array $opts = []): void
    {
        i\type_check($entity, Process::class);

        $entity->save($opts);
    }

    /**
     * Delete the entity from the DB.
     *
     * @param Process $entity
     * @param array $opts
     */
    public function delete(Entity $entity, array $opts = []): void
    {
        i\type_check($entity, Process::class);

        $entity->delete($opts);
    }

    /**
     * @internal
     * @param Process $process
     */
    public function autosave(Process $process): void
    {
        $this->save($process);
    }
}
