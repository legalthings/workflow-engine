<?php declare(strict_types=1);

/**
 * Service to run hooks after an action for the process.
 */
class HookManager
{
    /**
     * @var callable[]
     */
    protected $handlers = [];

    /**
     * Add a new trigger handler.
     * Creates a copy of the manager, as services are immutable.
     *
     * @param string|null $schema   Only trigger if action matches this schema.
     * @param callable    $trigger
     * @return static
     */
    public function with(?string $schema, callable $trigger): self
    {
        $clone = clone $this;

        $clone->handlers[] = function (Process $process, Action $payload) use ($trigger, $schema) {
            if ($schema !== null && $payload->schema !== $schema) {
                return $payload; // Not handled by this handler
            }

            $action = clone $payload;
            $trigger($process, $action);
        };

        return $clone;
    }

    /**
     * Invoke the hooks.
     *
     * @param Process $process
     */
    public function invoke(Process $process): void
    {
        if ($process->current->response === null) {
            throw new InvalidArgumentException("Current state doesn't have a response yet");
        }

        $action = $process->current->response->action;

        foreach ($this->handlers as $handler) {
            $handler($process, $action);
        }
    }

    /**
     * Invoke the hooks.
     *
     * @param Process $process
     */
    final public function __invoke(Process $process): void
    {
        $this->invoke($process);
    }
}
