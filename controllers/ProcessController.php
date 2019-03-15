<?php declare(strict_types=1);

use Jasny\ValidationException;

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
     * Account that signed the request.
     * @var \LTO\Account
     */
    protected $account;

    /**
     * Class constructor for DI.
     */
    public function __construct(
        ProcessGateway $processes,
        ScenarioGateway $scenarios,
        ProcessInstantiator $instantiator,
        ProcessStepper $stepper,
        TriggerManager $triggerManager
    ) {
        $this->setServices(func_get_args());
    }

    /**
     * Executed before each action.
     */
    public function before()
    {
        $this->account = $this->request->getAttribute('account');

        if ($this->account === null) {
            throw new AuthException('Request not signed', 401);
        }
    }

    /**
     * Start a new process
     */
    public function startAction(): void
    {
        $scenario = $this->getScenarioFromInput();

        $process = $this->instantiator->instantiate($scenario)
            ->setValues($this->getInput());
        $process->validate()->mustSucceed();
        
        $this->authzForAccount($process);

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
        $this->authzForAccount($process);

        $this->output($process);
    }

    /**
     * Handle a new response.
     */
    public function handleResponseAction(): void
    {
        $process = $this->getProcessFromInput();
        $this->authzForAccount($process);

        $response = (new Response)
            ->setValues($this->getInput())
            ->set('actor', $this->getActorForAccount());

        // Stepper does validation (in context of the current state of the process).
        $this->stepper->step($process, $response);

        $this->output($process);
    }

    /**
     * Invoke the triggers for the default action in a state.
     *
     * @param string $id  Process id
     */
    public function invokeAction(string $id): void
    {
        $process = $this->processes->fetch($id);
        $this->authzForAccount($process);

        $actor = $this->getActorForAccount($process);

        $this->triggerManager->invoke($process, null, $actor);
    }

    /**
     * Delete process
     *
     * @param string $id
     */
    public function deleteAction(string $id): void
    {
        $process = $this->processes->fetch($id);
        $this->authzForAccount($process);

        $this->processes->delete($process);
    }


    /**
     * Get the scenario id from the posted data and fetch the scenario.
     *
     * @return Scenario
     */
    protected function getScenarioFromInput(): Scenario
    {
        $input = $this->getInput();
        $scenarioId = $input['scenario']['id'] ?? $input['scenario'] ?? null;

        if (!is_string($scenarioId)) {
            throw ValidationException::error('Scenario not specified');
        }

        return $this->scenarios->fetch($scenarioId);
    }

    /**
     * Get the process id from the posted data and fetch the process.
     *
     * @return Process
     */
    protected function getProcessFromInput(): Process
    {
        $input = $this->getInput();
        $processId = $input['process']['id'] ?? $input['process'] ?? null;

        if (!is_string($processId)) {
            throw ValidationException::error('Process not specified');
        }

        return $this->processes->fetch($processId);
    }

    /**
     * Check if the account that signed request is an actor in the process.
     *
     * @param Process $process
     */
    protected function authzForAccount(Process $process): void
    {
        $actor = $this->getActorForAccount();

        if (!$process->hasActor($actor)) {
            throw new AuthException("Process doesn't have " . $actor->describe());
        }
    }

    /**
     * Get actor from id or public sign key from the HTTP request.
     *
     * @return Actor
     */
    protected function getActorForAccount(): Actor
    {
        return (new Actor)->set('identity', Identity::fromAccount($this->account));
    }
}
