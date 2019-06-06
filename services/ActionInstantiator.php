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
            $action = clone $definition;
            $this->applyActionCondition($action, $process);            

            if (count($action->actors) > 0) {
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
     */
    public function applyActionCondition(Action $action, Process $process)
    {
        if (!$action->condition instanceof DataInstruction) {
            return;
        }

        $instruction = (string)$action->condition;
        $hasCurrentActor = strpos($instruction, 'current.actor') !== false;
        
        $hasCurrentActor ?
            $this->applyCurrentActorCondition($action, $process) :
            $this->dataEnricher->applyTo($action, $process);
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

        foreach ($action->actors as $idx => $actorKey) {
            $actor = $process->getActor($actorKey);
            $process->current->actor = $actor;

            $this->dataEnricher->applyTo($action, $process);

            if (is_bool($action->condition) && !$action->condition) {
                unset($action->actors[$idx]);
            }
        }
    }
}
