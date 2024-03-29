<?php declare(strict_types=1);

use LegalThings\DataEnricher;
use Jasny\DB\EntitySet;
use Carbon\CarbonImmutable;

/**
 * Instantiate a state from the state definition in the scenario.
 * @immutable
 */
class StateInstantiator
{
    /**
     * The data enricher evaluates data instructions.
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
     * @param DataEnricher  $dataEnricher
     */
    public function __construct(DataEnricher $dataEnricher, ActionInstantiator $actionInstantiator)
    {
        $this->dataEnricher = $dataEnricher;
        $this->actionInstantiator = $actionInstantiator;
    }

    /**
     * Instantiate a process state from a scenario state.
     *
     * @param State   $definition
     * @param Process $process
     * @return CurrentState
     * @throws RuntimeException
     */
    public function instantiate(State $definition, Process $process): CurrentState
    {
        try {
            $state = clone $definition;
            $this->dataEnricher->applyTo($state, $process);

            $currentState = CurrentState::fromData($state->toData());
            $currentState->due_date = $this->calcDueDate($state);

            $actionDefinitions = $process->scenario->getActionsForState($definition);
            $currentState->actions = $this->actionInstantiator->instantiate($actionDefinitions, $process);

            if (isset($process->current->response)) {
                $currentState->actor = $process->current->response->actor;
            }
        } catch (RuntimeException $e) {
            $msg = "Failed to instantiate state '%s' for process '%s': %s";
            $err = sprintf($msg, $definition->title ?: $definition->key, $process->id, $e->getMessage());
            throw new \RuntimeException($err, 0, $e);
        }

        return $currentState;
    }

    /**
     * Recalculate (the data instructions of) the actions of the current state.
     *
     * @param Process $process
     */
    public function recalcActions(Process $process): void
    {
        $actionDefinitions = $process->scenario->getActionsForState($process->current->key);        
        $process->current->actions = $this->actionInstantiator->instantiate($actionDefinitions, $process);
    }

    /**
     * Recalculate (the data instructions of) the transitions of the current state.
     *
     * @param Process $process
     */
    public function recalcTransitions(Process $process): void
    {
        $process->current->transitions = [];
        $process->current->cast();

        $transitionDefinitions = $process->scenario->getState($process->current->key)->transitions;

        foreach ($transitionDefinitions as $definition) {
            $transition = clone $definition;
            $this->dataEnricher->applyTo($transition, $process);

            $process->current->transitions[] = $transition;
        }
    }

    /**
     * Get the due date for the given state timeout.
     *
     * @param State $state
     * @return DateTimeImmutable|null
     * @throws Exception when the interval_spec cannot be parsed as an interval.
     */
    protected function calcDueDate(State $state): ?DateTimeImmutable
    {
        return $state->timeout !== null
            ? CarbonImmutable::now('UTC')->add(new DateInterval($state->timeout))
            : null;
    }
}
