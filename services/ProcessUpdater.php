<?php declare(strict_types=1);

use Jasny\ValidationResult;
use Jasny\DB\EntitySet;
use Jasny\EventDispatcher\EventDispatcher;

/**
 * Update a process that has a response for the current state.
 * Apply update instructions, determine the transaction and instantiate the new current state.
 * @immutable
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
     * Get next states for a process. You can use a stub function if your implementation doesn't need to determine the
     * (golden) flow after each state change. Simulation can be heavy on performance.
     *
     * @var ProcessSimulator
     */
    protected $simulator;


    /**
     * ProcessUpdater constructor.
     *
     * @param StateInstantiator $stateInstantiator
     * @param DataPatcher       $pather
     * @param ProcessSimulator  $simulator
     */
    public function __construct(StateInstantiator $stateInstantiator, DataPatcher $patcher, ProcessSimulator $simulator)
    {
        $this->stateInstantiator = $stateInstantiator;
        $this->patcher = $patcher;
        $this->simulator = $simulator;
    }

    /**
     * Update the state of a process.
     *
     * @param Process $process
     * @return ValidationResult
     */
    public function update(Process $process): ValidationResult
    {
        if (!isset($process->current->response)) {
            $msg = "Can't update process '%s' in state '%s' without a response";
            throw new InvalidArgumentException(sprintf($msg, $process->id, $process->current->key));
        }

        $this->applyCurrentUpdateInstructions($process);
        $validation = $process->validate();

        if ($validation->failed()) {
            return $validation;
        }

        $process->dispatch('update');

        $this->changeCurrentState($process);

        $process->next = $this->simulator->getNextStates($process);

        return ValidationResult::success();
    }

    /**
     * Apply update instructions from the current state.
     *
     * @param Process $process
     * @throws RuntimeException
     */
    protected function applyCurrentUpdateInstructions(Process $process): void
    {
        $this->stateInstantiator->recalcActions($process);
        $updateInstructions = $this->getUpdateInstructions($process);

        if (count($updateInstructions) === 0) {
            return;
        }

        $responseData = $process->current->response->data;

        foreach ($updateInstructions as $updateInstruction) {
            $this->applyUpdateInstruction($process, $updateInstruction, $responseData);
        }

        $process->cast();
    }

    /**
     * Get the update instructions for the current state
     *
     * @param Process $process
     * @return EntitySet&iterable<UpdateInstruction>
     */
    protected function getUpdateInstructions(Process $process): EntitySet
    {
        $response = $process->current->response;
        $action = $response->action;

        return $process->current->actions[$action->key]->responses[$response->key]->update
            ?? EntitySet::forClass(UpdateInstruction::class);
    }

    /**
     * Apply an update instruction to a process.
     *
     * @param Process           $process
     * @param UpdateInstruction $updateInstructions
     * @param mixed             $responseData
     * @throws RuntimeException
     */
    protected function applyUpdateInstruction(Process $process, UpdateInstruction $update, $responseData): void
    {
        $data = $update->data ?? $responseData;

        if ($update->projection !== null) {
            $data = $this->patcher->project($data, $update->projection);
        }

        $this->patcher->set($process, $update->select, $data, $update->patch);

        // Todo; only cast what has been updated instead of everything
        $this->castAll($process->assets, $process->scenario->assets);
        $this->castAll($process->actors, $process->scenario->actors);
    }

    /**
     * Change the current state of the process
     *
     * @param Process $process
     * @throws RuntimeException
     */
    protected function changeCurrentState(Process $process): void
    {
        $response = $process->current->response;
        $scenario = $process->scenario;

        $process->previous[] = $response;

        $this->stateInstantiator->recalcTransitions($process);
        $transition = $process->current->getTransition($response);

        // No state transition in this state for given response.
        if ($transition === null) {
            return;
        }

        $scenarioState = $scenario->getState($transition->goto);
        $process->current = $this->stateInstantiator->instantiate($scenarioState, $process);
    }

    /**
     * Cast all entities
     *
     * @param iterable     $entities
     * @param JsonSchema[] $schemas
     */
    protected function castAll(iterable $entities, $schemas): void
    {
        foreach ($entities as $key => $entity) {
            $schemas[$key]->typeCast($entity);
        }
    }
}
