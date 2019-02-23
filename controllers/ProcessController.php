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
     * Start a new process
     */
    public function startAction(): void
    {
        $scenario = $this->getScenarioFromInput();

        $process = $this->instantiator->instantiate($scenario);
        $process->validate()->mustSucceed();
        
        $this->getActorFromRequest($process); // For auth

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

        $this->getActorFromRequest($process); // Only for auth

        $this->output($process);
    }

    /**
     * Handle a new response.
     */
    public function handleResponseAction(): void
    {
        $process = $this->getProcessFromInput();

        $response = (new Response)
            ->setValues($this->getInput())
            ->set('actor', $this->getActorFromRequest($process));

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
        $actor = $this->getActorFromRequest($process);

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

        $this->getActorFromRequest($process); // Auth

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
     * Get actor id or public sign key from the HTTP request.
     *
     * @param Process $process
     * @return Actor
     */
    protected function getActorFromRequest(Process $process): Actor
    {
        $info = $this->request->getAttribute('identity') ?? $this->request->getAttribute('account');

        if ($info === null) {
            throw new AuthException('Request not signed or identity not specified');
        }

        $actor = $info instanceof \LTO\Account
            ? (new Actor())->set('signkeys', [$info->getPublicSignKey()])
            : (new Actor())->set('identity', $info);

        if (!$process->hasActor($actor)) {
            throw new AuthException("Process doesn't have " . $actor->describe());
        }

        return $actor;
    }
}
