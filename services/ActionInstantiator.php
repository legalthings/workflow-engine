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
            $action = $this->applyActionCondition($definition, $process);            

            if ((bool)$action->condition) {
                $actions[$action->key] = $action;
            }
        }

        return $actions;
    }

    /**
     * Apply action condition
     *
     * @param Action $action
     * @param Process $process
     * @return Action           Action, processed with data enricher   
     */
    public function applyActionCondition(Action $definition, Process $process): Action
    {
        $action = clone $definition;

        $instruction = (string)$action->condition;
        $hasCurrentActor = strpos($instruction, 'current.actor') !== false;
        
        $hasCurrentActor ?
            $this->applyCurrentActorCondition($action, $process) :
            $this->dataEnricher->applyTo($action, $process);

        return $action;
    }

    /**
     * Apply action condition, if it holds reference to current actor
     *
     * @param Action $action
     * @param Process $process
     */
    protected function applyCurrentActorCondition(Action $action, Process $process)
    {
        $process = clone $process;
        if (!isset($process->current)) {
            $process->current = new CurrentState();
        }

        $condition = $action->condition;

        foreach ($action->actors as $idx => $actorKey) {
            $actor = $process->getActor($actorKey);
            $process->current->actor = $actor;

            $this->dataEnricher->applyTo($action, $process);

            if (is_bool($action->condition) && !$action->condition) {
                unset($action->actors[$idx]);
            }

            // restore condition expression to calculate it again for next actor
            $action->condition = $condition;
        }

        $action->condition = count($action->actors) > 0;
    }
}
