<?php declare(strict_types=1);

use Improved as i;
use Improved\IteratorPipeline\Pipeline;
use Jasny\DB\EntitySet;
use LegalThings\DataEnricher;
use function Jasny\object_get_properties;

/**
 * The process walker is used to determine the next states of the process by following the default actions and
 * default responses. At the start of the process this results in the 'golden flow'. If an alternative path is taken,
 * the subsequent states show the likely path that will be followed.
 *
 * All actions and state transfers will evaluated using the current process state. This can affect the outcome of
 * data instructions.
 */
class ProcessWalker
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
     *
     * @param Process $process
     * @return EntitySet&iterable<NextState>
     */
    public function getNextStates(Process $process)
    {
        $scenario = $process->scenario;

        $defaultAction = $process->current->getDefaultAction();
        $transition = $process->current->getTransition($defaultAction->key, $defaultAction->default_response);

        $state = $scenario->getState($transition->transition);

        /** @var EntitySet&iterable<NextState> $next */
        $index = [];
        $next = EntitySet::forClass(NextState::class);

        while ($state !== null && !in_array($state->key, $index)) {
            $action = $this->getDefaultAction($state, $process);

            $nextState = $this->instantiateNextState($state, $process);
            $nextState->actor = isset($action) ? $action->actor : null;

            $index[] = $state->key;
            $next[] = $nextState;

            $transition = isset($action)
                ? $this->getTransition($state, $action->key, $action->default_response, $process)
                : null;

            $state = isset($transition) ? $scenario->getState($transition->transition) : null;
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
        return i\iterable_find($state->actions, function (Action $action) use ($process) {
            $condition = $action->condition;

            if ($action->condition instanceof DataInstruction) {
                $condition = clone $action->condition;
                $this->dataEnricher->applyTo($condition, $process);
            }

            return $condition === true;
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
        Pipeline::with($state->transitions)
            ->filter(function(StateTransition $transition) use ($action, $response) {
                return
                    (!isset($transition->action) || $transition->action === $action) &&
                    (!isset($transition->response) || $transition->response === $response);
            })
            ->filter(function(StateTransition $transition) use ($process) {
                $condition = $transition->condition;

                if ($transition->condition instanceof DataInstruction) {
                    $condition = clone $transition->condition;
                    $this->dataEnricher->applyTo($condition, $process);
                }

                return $condition === true;
            })
            ->first();
    }

    /**
     * Instantiate a scenario state as next state.
     *
     * @param State   $state
     * @param Process $process
     * @return NextState
     */
    protected function instantiateNextState(State $state, Process $process): NextState
    {
        $nextState = new NextState();

        foreach (array_keys(object_get_properties($nextState)) as $property) {
            $value = $state->$property ?? null;

            if (is_object($value)) {
                $value = clone $value;
            }

            if ($value instanceof DataInstruction) {
                $this->dataEnricher->applyTo($value, $process);
            }

            $nextState->$property = $value;
        }

        return $nextState;
    }
}
