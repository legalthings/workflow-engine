<?php declare(strict_types=1);

use LegalThings\DataEnricher;

/**
 * Instantiate process actions, resolving conditions
 * @immutable
 */
class ActionInstantiator
{
    /**
     * Class constructor.
     *
     * @param DataEnricher  $dataEnricher
     */
    public function __construct(DataEnricher $dataEnricher) 
    {
        $this->dataEnricher = $dataEnricher;
    }

    /**
     * Instantiate a process from a scenario.
     *
     * @param Scenario $scenario
     * @return Process
     */
    public function instantiate(iterable $actionDefinitions, Process $process): AssocEntitySet
    {
        $actions = AssocEntitySet::forClass(Action::class);        

        foreach ($actionDefinitions as $definition) {            
            $action = $this->enrichAction($definition, $process);            

            if ((bool)$action->condition) {
                $actions[$action->key] = $action;
            }
        }

        return $actions;
    }

    /**
     * Apply data enricher to action
     *
     * @param Action $action
     * @param Process $process
     * @return Action           Action, processed with data enricher   
     */
    public function enrichAction(Action $definition, Process $process): Action
    {
        $action = clone $definition;

        $condition = (string)$action->condition;
        $hasCurrentActor = strpos($condition, 'current.actor') !== false;
        
        $hasCurrentActor ?
            $this->enrichWithCurrentActor($action, $process) :
            $this->dataEnricher->applyTo($action, $process);

        return $action;
    }

    /**
     * Apply data enricher, if condition holds reference to current actor
     *
     * @param Action $action
     * @param Process $process
     */
    protected function enrichWithCurrentActor(Action $action, Process $process)
    {
        // Prevent changing process
        $process = clone $process;
        $process->current = isset($process->current) ? clone $process->current : new CurrentState();

        $condition = $action->condition;

        foreach ($action->actors as $idx => $actorKey) {
            $actor = $process->getActor($actorKey);
            $process->current->actor = $actor;

            $this->dataEnricher->applyTo($action, $process);

            if (!(bool)$action->condition) {
                unset($action->actors[$idx]);
            }

            // restore condition expression to calculate it again for next actor
            $action->condition = $condition;
        }

        $action->condition = count($action->actors) > 0;
    }
}
