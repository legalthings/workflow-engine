<?php

declare(strict_types=1);

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
     * @var bool
     */
    protected $allowFullReset;

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
     * Account that signed the request.
     * @var \LTO\EventChain|null
     */
    protected $chain;

    /**
     * @var EventChainRepository
     **/
    protected $chainRepository;

    /**
     * Class constructor for DI.
     *
     * @param bool|mixed $allowFullReset  "allow_full_reset"
     */
    public function __construct(
        $allowFullReset,
        ProcessGateway $processes,
        ScenarioGateway $scenarios,
        ProcessInstantiator $instantiator,
        ProcessStepper $stepper,
        TriggerManager $triggerManager,
        JsonView $jsonView,
        EventChainRepository $chainRepository
    ) {
        $this->allowFullReset = (bool)$allowFullReset;
        $this->processes = $processes;
        $this->scenarios = $scenarios;
        $this->instantiator = $instantiator;
        $this->stepper = $stepper;
        $this->triggerManager = $triggerManager;
        $this->jsonView = $jsonView;
        $this->chainRepository = $chainRepository;
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

        $this->chain = $this->request->getAttribute('event-chain');
    }

    /**
     * List processes (the current identity has a role in).
     */
    public function listAction(): void
    {
        $identity = Identity::fetch(['signkeys.default' => $this->account->getPublicSignKey()]);

        if ($identity === null) {
            $this->output([]);
            return;
        }

        $processes = $this->processes->fetchList(['actor' => $identity]);
        $this->output($processes);
    }

    /**
     * Start a new process
     */
    public function startAction(): void
    {
        $scenario = $this->getScenarioFromInput();

        $process = $this->instantiator->instantiate($scenario)->setValues($this->getInput());
        $process->validate()->mustSucceed();

        $this->authzForAccount($process);

        $this->processes->save($process);

        $this->created('/processes/' . $process->id);
    }

    /**
     * Get a process
     */
    public function getAction(?string $id = null): void
    {
        $process = $this->processes->fetch($id);
        $this->authzForAccount($process);

        $this->output($process);
    }

    /**
     * Handle a new response.
     */
    public function handleResponseAction(?string $id = null): void
    {
        $process = $this->getProcessFromInput($id);
        $this->authzForAccount($process);

        $response = (new Response)
            ->setValues($this->getInput())
            ->set('actor', $this->getActorForAccount());

        // Stepper does validation (in context of the current state of the process).
        $this->stepper->step($process, $response);

        $this->persistNewEvents($process->chain);
    }

    /**
     * Invoke the triggers for the default action in a state.
     */
    public function invokeAction(string $id): void
    {
        $process = $this->getProcessFromInput($id);
        $this->authzForAccount($process);

        $actor = $this->getActorForAccount($process);
        $response = $this->triggerManager->invoke($process, null, $actor);

        if ($response !== null) {
            $this->chainRepository->addResponse($process->chain, $response);
        }

        $this->persistNewEvents($process->chain);
    }

    /**
     * Persist new events.
     * Output the events for the current chain.
     *
     * @throws RuntimeException if event chain uri is not configured and changes are made for other chains.
     */
    protected function persistNewEvents(?string $chainId = null): void
    {
        $newEvents = $chainId !== null ? $this->chainRepository->getPartial($chainId) : null;

        if ($newEvents !== null) {
            $this->chainRepository->register($newEvents); // mark current chain as persisted
        }

        // Also persist new events of other chains
        $this->chainRepository->persistAll();

        if ($newEvents !== null) {
            $this->output($newEvents, 'json');
        } else {
            $this->noContent();
        }
    }

    /**
     * Delete process
     */
    public function deleteAction(string $id): void
    {
        $process = $this->processes->fetch($id);
        $this->authzForAccount($process);

        $this->processes->delete($process);

        $this->noContent();
    }

    /**
     * Reset all chains.
     */
    public function resetAction(): void
    {
        $identity = Identity::fetch(['signkeys.default' => $this->account->getPublicSignKey()]);

        if ($identity === null) {
            $this->output([]);
            return;
        }

        $processes = $this->processes->fetchList(['actor' => $identity]);
        foreach ($processes as $process) {
            $this->processes->delete($process);
        }

        $this->noContent();
    }


    /**
     * Get the scenario id from the posted data and fetch the scenario.
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
     */
    protected function getProcessFromInput(?string $dir): Process
    {
        $idFromPath = $dir !== '-' ? $dir : null;

        $input = $this->getInput();
        $processId = $input['process']['id'] ?? $input['process'] ?? $idFromPath ?? null;

        if (!is_string($processId)) {
            throw ValidationException::error('Process not specified');
        }

        if ($idFromPath !== null && $processId !== $idFromPath) {
            throw ValidationException::error('Incorrect process id');
        }

        return $this->processes->fetch($processId);
    }

    /**
     * Check if the account that signed request is an actor in the process.
     */
    protected function authzForAccount(Process $process): void
    {
        $actor = $this->getActorForAccount();

        if (!$process->hasActor($actor) && $process->hasKnownActors()) {
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
