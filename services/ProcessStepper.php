<?php declare(strict_types=1);

use Improved as i;
use Jasny\ValidationResult;
use Jasny\ValidationException;
use LTO\Account;

/**
 * Handle a response and step through a process.
 * @immutable
 */
class ProcessStepper
{
    /**
     * @var ProcessUpdater
     */
    protected $processUpdater;

    /**
     * @var ActionInstantiator
     */
    protected $actionInstantiator;

    /**
     * ProcessStepper constructor.
     *
     * @param ProcessUpdater     $processUpdater
     * @param ActionInstantiator $actionInstantiator
     */
    public function __construct(ProcessUpdater $processUpdater, ActionInstantiator $actionInstantiator)
    {
        $this->processUpdater = $processUpdater;
        $this->actionInstantiator = $actionInstantiator;
    }

    /**
     * Step to the next state of the process.
     *
     * @param Process  $process
     * @param Response $response
     * @throws ValidationException
     */
    public function step(Process $process, Response $response): void
    {
        $this->validate($process, $response)->mustSucceed();

        $this->enrichCurrentResponse($process, $response);

        $this->processUpdater->update($process)->mustSucceed();
        $process->dispatch('step');
    }

    /**
     * Validate if the action and response are valid and allowed in the current state.
     *
     * @param Process  $process
     * @param Response $response
     * @return ValidationResult
     */
    protected function validate(Process $process, Response $response): ValidationResult
    {
        $actionKey = i\type_check($response->action ?? null, Action::class)->key;

        if (!isset($process->scenario->actions[$actionKey])) {
            return ValidationResult::error("Unknown action '%s'", $actionKey);
        }

        if (!isset($process->current->actions[$actionKey])) {
            return ValidationResult::error(
                "Action '%s' isn't allowed in state '%s'",
                $actionKey,
                $process->current->title ?? $process->current->key
            );
        }

        $currentAction = $process->current->actions[$actionKey];

        if (!$process->hasActor($response->actor)) {
            return ValidationResult::error("Unknown %s", $response->actor->describe());
        }

        if ($process->getActorForAction($currentAction->key, $response->actor) === null) {
            return ValidationResult::error(
                "%s isn't allowed to perform action '%s'",
                $process->getActor($response->actor)->describe(),
                $actionKey
            );
        }

        $responseKey = $currentAction->determine_response ?? $response->key;

        if (!$currentAction->isValidResponse($responseKey)) {
            return ValidationResult::error("Invalid response '%s' for action '%s'", $responseKey, $actionKey);
        }

        return ValidationResult::success();
    }

    /**
     * Enrich the current response of the process.
     *
     * @param Process  $process
     * @param Response $response
     */
    protected function enrichCurrentResponse(Process $process, Response $input): void
    {
        $process->current->response = $input;

        $actionDefinition = $process->scenario->actions[$input->action->key];
        $currentAction = $this->actionInstantiator->enrichAction($actionDefinition, $process);
        $responseKey = $currentAction->determine_response ?? $input->key;

        $availableResponse = $currentAction->getResponse($responseKey);

        $response = clone $input;
        $response->setValues($availableResponse->getValues());

        $response->action = $currentAction;
        $response->key = $responseKey;
        $response->action->responses = null;
        $response->action->actors = null;

        $response->actor = $process->getActorForAction($currentAction->key, $response->actor);

        $process->current->actor = $response->actor;
        $process->current->response = $response;
    }
}
