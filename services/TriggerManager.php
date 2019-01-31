<?php declare(strict_types=1);

use Jasny\EventDispatcher\EventDispatcher;
use Jasny\ValidationException;
use PHPUnit\Exception as PHPUnitException;

/**
 * Service to trigger automated action(s) for the process.
 * Triggering works via the event dispatcher.
 */
class TriggerManager
{
    /**
     * Add a trigger to the event dispatcher.
     *
     * @param EventDispatcher $dispatcher
     * @param ?string         $schema      Only trigger if action matches this schema.
     * @param callable $trigger
     */
    public function add(EventDispatcher &$dispatcher, ?string $schema, callable $trigger): void
    {
        $handler = function (Process $process, $payload) use ($trigger, $schema) {
            if (!$payload instanceof Action) {
                return $payload; // Already handled
            }

            if ($schema !== null && $payload->schema !== $schema) {
                return $payload; // Not handled by this handler
            }

            $action = clone $payload;

            try {
                $response = $trigger($process, $action);
            } catch (PHPUnitException $exception) {  // @codeCoverageIgnore
                throw $exception;                    // @codeCoverageIgnore
            } catch (RuntimeException $exception) {
                $response = $this->createErrorResponse($exception, $action);
            }

            return $response;
        };

        $dispatcher = $dispatcher->on('trigger', $handler);
    }

    /**
     * Invoke the trigger(s).
     *
     * @param Process     $process
     * @param string|null $actionKey  Key of the action that is triggered or null for default action.
     * @param string      $actorKey   Actor that will perform the action.
     * @return Response|null
     * @throws UnexpectedValueException
     */
    public function invoke(Process $process, ?string $actionKey, string $actorKey): ?Response
    {
        $processAction = $this->getAllowedAction($process, $actionKey, $actorKey);

        if ($processAction === null) {
            return null;
        }

        $action = clone $processAction;
        unset($action->actors);
        $action->actor = $process->getActor($actorKey);

        $result = $process->dispatch('trigger', $action);

        return $result instanceof Response ? $result : null;
    }

    /**
     * Get the action from the process. Assert that the specified actor may perform the action.
     *
     * @param Process     $process
     * @param string|null $actionKey
     * @param string      $actorKey
     * @return Action|null
     * @throws UnexpectedValueException
     */
    protected function getAllowedAction(Process $process, ?string $actionKey, string $actorKey): ?Action
    {
        $current = $process->current;

        if ($actionKey !== null && !isset($current->actions[$actionKey])) {
            $msg = "Action '%s' is not allowed in state '%s' of process '%s'";
            throw new UnexpectedValueException(sprintf($msg, $actionKey, $current->key, $process->id));
        }

        $action = $actionKey ? $current->actions[$actionKey] : $process->current->getDefaultAction();

        if ($action !== null && $action->isAllowedBy($actorKey)) {
            return $action;
        }

        if ($actionKey !== null) {
            throw new UnexpectedValueException(sprintf(
                "%s is not allowed to perform action '%s' in process '%s'",
                $process->getActor($actorKey)->title ?? "Actor '{$actorKey}'",
                $actionKey,
                $process->id
            ));
        }

        return null;
    }

    /**
     * Create an error response for a caught exception.
     *
     * @param RuntimeException $exception
     * @param Action           $action
     * @return Response
     */
    protected function createErrorResponse(RuntimeException $exception, Action $action): Response
    {
        $response = new Response();

        $response->key = 'error';
        $response->action = $action;
        $response->title = 'An error occured';
        $response->data = (object)['message' => $exception->getMessage()];

        if ($exception instanceof ValidationException) {
            $response->data->errors = $exception->getErrors();
        }

        return $response;
    }
}
