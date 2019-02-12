<?php declare(strict_types=1);

use Improved as i;
use Jasny\ValidationException;
use PHPUnit\Exception as PHPUnitException;

/**
 * Service to trigger automated action(s) for the process.
 * Triggering works via the event dispatcher.
 */
class TriggerManager
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

        $clone->handlers[] = function (Process $process, $payload) use ($trigger, $schema) {
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

        return $clone;
    }

    /**
     * Invoke the trigger(s).
     *
     * @param Process     $process
     * @param string|null $actionKey  Key of the action that is triggered or null for default action.
     * @param Actor       $actor      The actor that will perform the action.
     * @return Response|null
     * @throws UnexpectedValueException
     */
    public function invoke(Process $process, ?string $actionKey, Actor $actor): ?Response
    {
        $action = $this->getAllowedAction($process, $actionKey, $actor);

        if ($action === null) {
            return null;
        }

        $handlerResult = $this->trigger($process, $action);
        $result = $process->dispatch('trigger', $handlerResult);

        if (!$result instanceof Response) {
            return null;
        };

        $result->action = $action;
        $result->actor = clone $action->actor;
        unset($result->action->actor);

        return $result;
    }

    /**
     * Get the action from the process. Assert that the specified actor may perform the action.
     *
     * @param Process     $process
     * @param string|null $actionKey
     * @param Actor       $givenActor
     * @return Action|null
     * @throws UnexpectedValueException
     */
    protected function getAllowedAction(Process $process, ?string $actionKey, Actor $givenActor): ?Action
    {
        $current = $process->current;

        if ($actionKey !== null && !isset($current->actions[$actionKey])) {
            $msg = "Action '%s' is not allowed in state '%s' of process '%s'";
            throw ValidationException::error($msg, $actionKey, $current->key, $process->id);
        }

        $action = $actionKey ? $current->actions[$actionKey] : $process->current->getDefaultAction();

        if (!$process->hasActor($givenActor)) {
            $msg = "Actor '%s' is not an actor in process '%s'";
            throw ValidationException::error($msg, $actionKey, $current->key, $process->id);
        }

        $actor = $process->getActorForAction($action->key, $givenActor);

        if ($action !== null && $actor !== null) {
            // ok :-)
        } elseif ($actionKey === null) {
            return; // Default action can't be performed, no need for an error.
        } else {
            throw ValidationException::error(
                "%s is not allowed to perform action '%s' in process '%s'",
                $process->getActor($givenActor)->describe(),
                $actionKey,
                $process->id
            );
        }

        $triggerAction = clone $action;
        $triggerAction->actor = $actor;

        return $triggerAction;
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

    /**
     * Trigger an event.
     *
     * @param Process              $process
     * @param Action|Response|null $payload
     * @return Action|Response|null
     */
    protected function trigger(Process $process, $payload = null)
    {
        $callback = function ($payload, $handler) use ($process) {
            return i\function_call($handler, $process, $payload);
        };

        return i\iterable_reduce($this->handlers, $callback, $payload);
    }
}
