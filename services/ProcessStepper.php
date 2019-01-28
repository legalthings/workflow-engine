<?php declare(strict_types=1);

use Jasny\ValidationResult;
use Jasny\ValidationException;

/**
 * Handle a response and step through a process.
 */
class ProcessStepper
{
    /**
     * @var ProcessUpdater
     */
    protected $processUpdater;

    /**
     * ProcessStepper constructor.
     *
     * @param ProcessUpdater $processUpdater
     */
    public function __construct(ProcessUpdater $processUpdater)
    {
        $this->processUpdater = $processUpdater;
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

        $process->current->response = $this->expandResponse($process, $response);

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
        $actionKey = $response->action->key;
        $responseKey = $response->key;

        if (!isset($process->scenario->actions[$actionKey])) {
            return ValidationResult::error("Unknown action '%s'", $actionKey);
        }

        if (!isset($process->current->actions[$actionKey])) {
            $msg = "Action '%s' isn't allowed in state '%s'";
            return ValidationResult::error($msg, $actionKey, $process->current->title ?? $process->current->key);
        }

        $currentAction = $process->current->actions[$actionKey];

        if (!$currentAction->isAllowedBy($response->actor)) {
            $msg = "Actor '%s' isn't allowed to perform action '%s'";
            return ValidationResult::error($msg, $process->getActor($response->actor)->title, $actionKey);
        }

        if (!$currentAction->isValidResponse($responseKey)) {
            $msg = "Invalid response '%s' for action '%s'";
            return ValidationResult::error($msg, $response, $actionKey);
        }

        return ValidationResult::success();
    }

    /**
     * @param Process $process
     * @param Response $response
     * @return Response
     */
    protected function expandResponse(Process $process, Response $input): Response
    {
        $currentAction = $process->current->actions[$input->action->key];
        $availableResponse = $currentAction->getResponse($input->key);

        $response = clone $input;
        $response->setValues($availableResponse->getValues());

        $response->action = clone $currentAction;
        $response->action->responses = null;
        $response->action->actors = null;

        $response->actor = $process->getActor($response->actor->key);

        return $response;
    }
}