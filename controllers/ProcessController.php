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
     * @var LTO\Account
     */
    protected $account;

    /**
     * Account that signed the request.
     * @var LTO\EventChain|null
     */
    protected $chain;

    /**
     * @var EventChainRepository
     **/
    protected $chainRepository;

    /**
     * Class constructor for DI.
     */
    public function __construct(
        LTO\Account $node,
        ProcessGateway $processes,
        ScenarioGateway $scenarios,
        IdentityGateway $identities,
        ProcessInstantiator $instantiator,
        ProcessStepper $stepper,
        TriggerManager $triggerManager,
        JsonView $jsonView,
        EventChainRepository $chainRepository
    ) {
        object_init($this, get_defined_vars());
    }

    /**
     * Executed before each action.
     * @throws AuthException
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
        try {
            $identity = $this->identities->fetch(['signkeys.default' => $this->account->getPublicSignKey()]);
        } catch (EntityNotFoundException $exception) {
            $this->output([]);
            return;
        }

        $processes = $this->processes->fetchList(['actor' => $identity]);
        $this->output($processes);
    }

    /**
     * Start a new process
     * @throws AuthException
     * @throws ValidationException
     * @throws EntityNotFoundException
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
     *
     * @param string $id  Process id
     * @throws AuthException
     * @throws EntityNotFoundException
     */
    public function getAction(?string $id = null): void
    {
        $process = $this->processes->fetch($id);
        $this->authzForAccount($process);

        $this->output($process);
    }

    /**
     * Handle a new response.
     * @throws AuthException
     * @throws ValidationException
     * @throws EntityNotFoundException
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
     *
     * @param string $id Process id
     * @throws ValidationException
     * @throws EntityNotFoundException
     * @throws AuthException
     */
    public function invokeAction(string $id): void
    {
        $process = $this->getProcessFromInput($id);
        $this->authzForAccount($process);

        $actor = $this->getActorForAccount();
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
     * @param string|null $chainId
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
     *
     * @param string $id
     * @throws AuthException
     * @throws ValidationException
     * @throws EntityNotFoundException
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
     * @throws ValidationException
     * @throws EntityNotFoundException
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
     * @param string|null $idFromPath
     * @return Process
     * @throws ValidationException
     * @throws EntityNotFoundException
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
     *
     * @param Process $process
     * @throws AuthException
     */
    protected function authzForAccount(Process $process): void
    {
        $actor = $this->getActorForAccount();

        // Existing process
        if ($process->hasActor($actor)) {
            return;
        }

        if ($process->hasKnownActors()) {
            throw new AuthException("Process doesn't have " . $actor->describe());
        }

        // New process
        $this->authz(Identity::AUTHZ_USER, "Signing identity isn't allowed to create a process");
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
