<?php declare(strict_types=1);

use Jasny\EventDispatcher\EventDispatcher;
use Jasny\ValidationException;

/**
 * Service to trigger automated action(s) for the process.
 * Triggering works via the event dispatcher.
 */
class ProcessTrigger
{
    /**
     * Add a trigger to the event dispatcher.
     *
     * @param EventDispatcher $dispatcher
     * @param ?string         $schema      Only trigger if action matches this schema.
     * @param callable $trigger
     */
    public function add(EventDispatcher $dispatcher, ?string $schema, callable $trigger): void
    {
        $handler = function (string $event, Process $process, $payload) use ($trigger, $schema) {
            if (!$payload instanceof Action) {
                return $payload; // Already handled
            }

            if ($schema !== null && $process->schema !== $schema) {
                return $payload;
            }

            $action = clone $payload;

            try {
                $response = $trigger($event, $process, $action);
            } catch (RuntimeException $exception) {
                $response = $this->createErrorResponse($exception);
            }

            return $response;
        };

        $dispatcher->on('trigger', $handler);
    }

    /**
     * Invoke the trigger(s).
     *
     * @param Process $process
     * @param string|null $action Key of the action that is triggered, null for default action.
     * @return Response|null
     * @throws UnexpectedValueException
     */
    public function invoke(Process $process, string $action): ?Response
    {
        $current = $this->process->current;

        if ($action !== null && !$this->process->current->hasAction($action)) {
            $msg = "Action '%s' is not allowed in state '%s' of process '%s'";
            throw new UnexpectedValueException(sprintf($msg, $action, $current->key, $process->id));
        }

        $response = $this->process->dispatch('trigger', $action !== null ? $current->actions[$action] : null);

        return $response instanceof Response ? $response : null;
    }

    /**
     * Create an error response for a caught exception.
     *
     * @param RuntimeException $exception
     * @param Action           $action
     * @return Response
     */
    public function createErrorResponse(RuntimeException $exception, Action $action): Response
    {
        $response = new Response();

        $response->key = ':error';
        $response->action = $action;
        $response->title = 'An error occured';
        $response->data = (object)['message' => $exception->getMessage()];

        if ($exception instanceof ValidationException) {
            $response->data->errors = $exception->getErrors();
        }

        return $response;
    }
}
