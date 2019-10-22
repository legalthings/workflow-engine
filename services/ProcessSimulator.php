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
 * All actions and state transfers will be evaluated using the current process state. This can affect the outcome of
 * data instructions.
 */
class ProcessSimulator
{
    /**
     * @var DataEnricher
     */
    protected $dataEnricher;

    /**
     * @var ActionInstantiator
     **/
    protected $actionInstantiator;

    /**
     * Class constructor.
     *
     * @param DataEnricher $dataEnricher
     */
    public function __construct(DataEnricher $dataEnricher, ActionInstantiator $actionInstantiator)
    {
        $this->dataEnricher = $dataEnricher;
        $this->actionInstantiator = $actionInstantiator;
    }

    /**
     * Walk over the process to get the next default states chain
     *
     * @param Process $process
     * @return EntitySet&iterable<NextState>
     */
    public function getNextStates(Process $process): EntitySet
    {
        $process = clone $process;
        $process->simulated = 1;

        $scenario = $process->scenario;
        $state = $scenario->getState($process->current->key);
        $action = $this->getDefaultAction($state, $process);

        /** @var EntitySet&iterable<NextState> $next */
        $index = [];
        $next = EntitySet::forClass(NextState::class, [], null, EntitySet::ALLOW_DUPLICATES);

        while ($action !== null) {
            $transition = isset($action) ? $this->getTransition($state, $action, $process) : null;
            $state = isset($transition) ? $scenario->getState($transition->goto) : null;

            if ($state === null) {
                break;
            }

            $loopDetected = in_array($state->key, $index);

            $action = $this->getDefaultAction($state, $process);

            $index[] = $state->key;
            $next[] = $this->instantiateNextState($state, $process, $action->actors ?? []);

            if ($loopDetected) {
                $next[] = $this->createLoopState();
                $action = null;
            }
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

        try {
            return Pipeline::with($state->getActions())
                ->map(function(string $actionKey) use ($scenario) {
                    return $scenario->getAction($actionKey);
                })
                ->find(function(Action $action) use ($process) {
                    $resolvedAction = $this->actionInstantiator->enrichAction($action, $process);

                    return (bool)$resolvedAction->condition;
                });
        } catch (RuntimeException $exception) {
            $msg = "Error while getting default action for state '%s' in process: '%s': %s";
            trigger_error(sprintf($msg, $state->key, $process->id, $exception->getMessage()), E_USER_WARNING);

            return null;
        }
    }

    /**
     * Get the transition for the default response of the given action for a state.
     *
     * @param State   $state
     * @param Action  $action
     * @param Process $process
     * @return StateTransition|null
     */
    protected function getTransition(State $state, Action $action, Process $process): ?StateTransition
    {
        $defaultResponse = new Response($action);

        try {
            return Pipeline::with($state->transitions)
                ->filter(function(StateTransition $transition) use ($defaultResponse) {
                    return $transition->appliesTo($defaultResponse);
                })
                ->filter(function(StateTransition $transition) use ($process) {
                    if ($transition->condition instanceof DataInstruction) {
                        $transition = clone $transition;
                        $this->dataEnricher->applyTo($transition, $process);
                    }

                    return (bool)$transition->condition;
                })
                ->first();
        } catch (RuntimeException $exception) {
            $msg = "Error while getting transition for state '%s' in process: '%s': %s";
            trigger_error(sprintf($msg, $state->key, $process->id, $exception->getMessage()), E_USER_WARNING);

            return null;
        }
    }

    /**
     * Instantiate a scenario state as next state.
     *
     * @param State                    $state
     * @param Process                  $process
     * @param string[]|DataInstruction $actor
     * @return NextState
     */
    protected function instantiateNextState(State $state, Process $process, $actors = []): NextState
    {
        /** @var NextState $nextState */
        $nextState = object_copy_properties($state, new NextState());
        $nextState->actors = $actors;

        try {
            $this->dataEnricher->applyTo($nextState, $process);
        } catch (RuntimeException $exception) {
            $msg = "Error while creating simulated state '%s' in process: '%s': %s";
            trigger_error(sprintf($msg, $state->key, $process->id, $exception->getMessage()), E_USER_WARNING);
        }

        return $nextState;
    }

    /**
     * Create a next state that indicates a loop.
     *
     * @return NextState
     */
    protected function createLoopState(): NextState
    {
        $nextState = new NextState();

        $nextState->key = ':loop';
        $nextState->title = '...';

        return $nextState;
    }
}
