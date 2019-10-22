<?php

declare(strict_types=1);

use Jasny\DB\Entity\Dynamic;
use Jasny\DB\Entity\Validation;
use Jasny\DB\Entity\Meta;
use Jasny\ValidationResult;

/**
 * Declaration of a state transition
 */
class StateTransition extends BasicEntity implements Dynamic, Meta, Validation
{
    use DeepClone;
    use Meta\Implementation;

    /**
     * Action and response reference
     * @var string
     */
    public $on;
    
    /**
     * Condition that must be true
     * @var boolean|DataInstruction
     */
    public $condition = true;
    
    /**
     * Reference of the state to transition to
     * @var string
     */
    public $goto;


    /**
     * Get the key of the action that the transition applies to.
     *
     * @return string
     */
    public function getActionKey()
    {
        if (!isset($this->on)) {
            throw new LogicException("Transition not configured: 'on' property not set");
        }

        return explode('.', $this->on, 2)[0];
    }

    /**
     * Get the key of the response that the transition applies to.
     *
     * @return string
     */
    public function getResponseKey()
    {
        if (!isset($this->on)) {
            throw new LogicException("Transition not configured: 'on' property not set");
        }

        return explode('.', $this->on, 2)[1] ?? '*';
    }

    /**
     * See if the transition should be triggered for the given response.
     *
     * @param Response $response
     * @return bool
     */
    public function appliesTo(Response $response): bool
    {
        if (!isset($this->on)) {
            throw new LogicException("Transition not configured: 'on' property not set");
        }

        [$actionKey, $responseKey] = explode('.', $this->on, 2) + ['*', '*'];

        return ($actionKey === '*' || $response->action->key === $actionKey)
            && ($responseKey === '*' || $response->key === $responseKey);
    }

    /**
     * See if transition condition is met.
     *
     * @return bool
     */
    public function meetsCondition(): bool
    {
        if ($this->condition instanceof DataInstruction) {
            throw new LogicException("Transition condition not evaluated");
        }

        return $this->condition;
    }

    /**
     * Validate the state transition.
     *
     * @return ValidationResult
     */
    public function validate(): ValidationResult
    {
        return ValidationResult::success();
    }
}
