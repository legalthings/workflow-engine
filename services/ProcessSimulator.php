<?php declare(strict_types=1);

use Improved\IteratorPipeline\Pipeline;
use Jasny\DB\EntitySet;
use LegalThings\DataEnricher;

/**
 * The process walker is used to determine the next states of the process by following the default actions and
 * default responses. At the start of the process this results in the 'golden flow'. If an alternative path is taken,
 * the subsequent states show the likely path that will be followed.
 *
 * Ends at a final state, a state where none of the transitions are available or when looping back on an state that is
 * already seen.
 *
 * All actions and state transfers will evaluated using the current process state. This can affect the outcome of
 * data instructions.
 */
class ProcessSimulator
{
    /**
     * @var DataEnricher
     */
    protected $dataEnricher;

    /**
     * Class constructor.
     *
     * @param DataEnricher $dataEnricher
     */
    public function __construct(DataEnricher $dataEnricher)
    {
        $this->dataEnricher = $dataEnricher;
    }

    /**
     * Walk over the process to get the next states.
     * @todo Clean up this loop, it's hard to follow. Maybe use pipeline instead.
     *
     * @param Process $process
     * @return EntitySet&iterable<NextState>
     */
    public function getNextStates(Process $process)
    {
        $process = clone $process;
        $process->simulated = 1;

        $scenario = $process->scenario;
        $state = $scenario->getState($process->current->key);
        $nextAction = $this->getDefaultAction($state, $process);

        /** @var EntitySet&iterable<NextState> $next */
        $index = [];
        $next = EntitySet::forClass(NextState::class, [], null, EntitySet::ALLOW_DUPLICATES);

        try {
            while ($state !== null) {
                // Determine next state
                $action = $nextAction;
                $transition = isset($action)
                    ? $this->getTransition($state, $action->key, $action->default_response, $process)
                    : null;
                $state = isset($transition) ? $scenario->getState($transition->transition) : null;

                // Looped back to already visited state
                if ($state !== null && in_array($state->key, $index)) {
                    break;
                }

                // Add next state
                if ($state !== null) {
                    $nextAction = $this->getDefaultAction($state, $process);

                    $index[] = $state->key;
                    $next[] = $this->instantiateNextState($state, $process, $nextAction->actors ?? []);
                }
            }
        } catch (RuntimeException $exception) {
            trigger_error($exception->getMessage(), E_USER_WARNING);
        }

        return $next;
    }

    /**
     * Get the default action for a scenario state.
     * Does not instantiate the state or action, only evaluates the condition.
     *
     * @param State $state
     * @param Process $process
     * @return Action|null
     */
    protected function getDefaultAction(State $state, Process $process): ?Action
    {
        $scenario = $process->scenario;

        return Pipeline::with($state->actions)
            ->map(function(string $actionKey) use ($scenario) {
                return $scenario->getAction($actionKey);
            })
            ->find(function(Action $action) use ($process) {
                $condition = $action->condition;

                if ($action->condition instanceof DataInstruction) {
                    $condition = clone $action->condition;
                    $this->dataEnricher->applyTo($condition, $process);
                }

                return (bool)$condition;
            });
    }

    /**
     * Get the transition for the default response of the given action for a state.
     *
     * @param State   $state
     * @param string  $action
     * @param string  $response
     * @param Process $process
     * @return StateTransition|null
     */
    protected function getTransition(State $state, string $action, string $response, Process $process): ?StateTransition
    {
        return Pipeline::with($state->transitions)
            ->filter(function(StateTransition $transition) use ($action, $response) {
                return
                    ($transition->action === $action || $transition->action === null) &&
                    ($transition->response === $response || $transition->response === null);
            })
            ->filter(function(StateTransition $transition) use ($process) {
                $condition = $transition->condition;

                if ($transition->condition instanceof DataInstruction) {
                    $condition = clone $transition->condition;
                    $this->dataEnricher->applyTo($condition, $process);
                }

                return (bool)$condition;
            })
            ->first();
    }

    /**
     * Instantiate a scenario state as next state.
     *
     * @param State    $state
     * @param Process  $process
     * @param string[] $actor
     * @return NextState
     */
    protected function instantiateNextState(State $state, Process $process, array $actors = []): NextState
    {
        $nextState = object_copy_properties($state, new NextState());

        $this->dataEnricher->applyTo($nextState, $process);
        $nextState->actors = $actors;

        return $nextState;
    }

    /**
     * Alias of `getNextStates()`.
     *
     * @param Process $process
     * @return EntitySet&iterable<NextState>
     */
    final public function __invoke(Process $process): EntitySet
    {
        return $this->getNextStates($process);
    }
}
