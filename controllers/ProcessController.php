<?php

/**
 * Process controller.
 *
 * `NotFoundMiddleware` and `ValidationMiddleware` are responsible for giving 40x responses for exceptions thrown by
 * the model related services.
 */
class ProcessController extends BaseController
{
    /**
     * @var ProcessGateway
     */
    protected $processes;

    /**
     * @var ScenarioGateway
     */
    protected $scenarios;

    /**
     * @var ProcessInstantiator
     */
    protected $instantiator;

    /**
     * @var ProcessStepper
     */
    protected $stepper;

    /**
     * @var TriggerManager
     */
    protected $triggerManager;

    /**
     * @var LTO\Account
     */
    protected $node;


    /**
     * Class constructor for DI.
     */
    public function __construct(
        ProcessGateway $processes,
        ScenarioGateway $scenarios,
        ProcessInstantiator $instantiator,
        ProcessStepper $stepper,
        TriggerManager $triggerManager,
        LTO\Account $node
    ) {
        $this->processes = $processes;
        $this->scenarios = $scenarios;
        $this->instantiator = $instantiator;
        $this->stepper = $stepper;
        $this->triggerManager = $triggerManager;
        $this->node = $node;
    }


    /**
     * Get the scenario id from the posted data and fetch the scenario.
     *
     * @return Scenario
     * @throws EntityNotFoundException
     */
    protected function getScenarioFromInput(): Scenario
    {
        $input = $this->getInput();
        $scenarioId = $input['scenario']['id'] ?? $input['scenario'] ?? null;

        if (!is_string($scenarioId)) {
            return $this->badRequest("Scenario not specified");
        }

        return $this->scenarios->fetch($scenarioId);
    }

    /**
     * Get the process id from the posted data and fetch the process.
     *
     * @return Process
     * @throws EntityNotFoundException
     */
    protected function getProcessFromInput(): Scenario
    {
        $input = $this->getInput();
        $processId = $input['process']['id'] ?? $input['process'] ?? null;

        if (!is_string($processId)) {
            return $this->badRequest("Process not specified");
        }

        return $this->processes->fetch($processId);
    }


    /**
     * Start a new process
     */
    public function startAction(): void
    {
        $scenario = $this->getScenarioFromInput();

        $process = $this->instantiator->instantiate($scenario);
        $process->save();

        $this->output($process);
    }

    /**
     * Get a process
     *
     * @param string $id  Process id
     */
    public function getAction(string $id): void
    {
        $process = $this->processes->fetch($id);

        $this->output($process);
    }

    /**
     * Handle a new response.
     */
    public function handleResponseAction(): void
    {
        $process = $this->getProcessFromInput();
        $response = (new Response)->setValues($this->getInput());

        $this->stepper->step($process, $response);

        $this->output($process);
    }

    /**
     * Invoke the triggers for the default action in a state.
     * @todo Process::whoAmI() doesn't exist and TriggerManager::invoke() only takes a single actor.
     *
     * @param string $id  Process id
     */
    public function invokeAction(string $id): void
    {
        $process = $this->processes->fetch($id);
        $actors = $process->whoAmI($this->node);

        $this->triggerManager->invoke($process, null, $actors->key);
    }


    /**
     * Update a scenario meta information.
     *
     * @param string $id  Scenario id
     */
    public function updateMetaAction($id)
    {
        $process = $this->processes->fetch($id);

        $process->meta->setValues($this->getInput());
        $this->processes->save($process, ['only' => 'meta']);

        $this->output($process);
    }
}
