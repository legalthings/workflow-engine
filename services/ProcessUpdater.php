<?php declare(strict_types=1);

use Improved as i;

/**
 * Update a process that has a response for the current state.
 * Apply update instructions, determine the transaction and instantiate the new current state.
 */
class ProcessUpdater
{
    /**
     * @var StateInstantiator
     */
    protected $stateInstantiator;

    /**
     * @var DataPatcher
     */
    protected $patcher;

    /**
     * Get next states for a process. You can use a stub function is your implementation doesn't need to determine the
     * (golden) flow after each state change. Simulation can be heavy on performance.
     *
     * @var ProcessSimulator|callable
     */
    protected $simulate;


    /**
     * ProcessUpdater constructor.
     *
     * @param StateInstantiator $stateInstantiator
     * @param DataPatcher       $pather
     * $param ProcessSimulator  $simulate
     */
    public function __construct(StateInstantiator $stateInstantiator, DataPatcher $patcher, callable $simulate)
    {
        $this->stateInstantiator = $stateInstantiator;
        $this->patcher = $patcher;
        $this->simulate = $simulate;
    }

    /**
     * Update the state of a process.
     *
     * @param Process $process
     */
    public function update(Process $process): void
    {
        if (!isset($process->current->response)) {
            $msg = "Can't update process '%s' in state '%s' without a response";
            throw new InvalidArgumentException(sprintf($msg, $process->id, $process->current->key));
        }

        $response = $process->current->response;
        $action = $process->action;
        $data = $response->data;

        foreach ($process->current->actions[$action->key]->responses[$response->key]->update as $updateInstruction) {
            $this->applyUpdateInstruction($process, $updateInstruction, $data);
        }

        $this->changeCurrentState($process);

        $process->next = i\function_call($this->simulate, $process);
    }

    /**
     * Change the current state of the process
     *
     * @param Process $process
     * @throws RuntimeException
     */
    public function changeCurrentState(Process $process): void
    {
        $response = $process->current->response;
        $action = $process->action;
        $scenario = $process->scenario;

        // Re-instantiate current state for data instructions in transition conditions
        $oldState = $this->stateInstantiator->instantiate($scenario->getState($process->current->key), $process);

        $transition = $oldState->getTransition($action->key, $response->key);
        $scenarioState = $scenario->getState($transition->transition);

        $process->current = $this->stateInstantiator->instantiate($scenarioState, $process);
    }

    /**
     * Apply an update instruction to a process.
     *
     * @param Process           $process
     * @param UpdateInstruction $updateInstructions
     * @param mixed             $data
     * @throws RuntimeException
     */
    protected function applyUpdateInstruction(Process $process, UpdateInstruction $updateInstruction, $data): void
    {
        $select = $updateInstruction->select;
        $projection = $this->patcher->project($updateInstruction->data ?? $data, $updateInstruction->projection);

        $this->patcher->set($process, $select, $projection, $updateInstruction->patch);
    }
}
