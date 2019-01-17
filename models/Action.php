<?php

use Jasny\DB\BasicEntity;
use Jasny\DB\Entity\Dynamic;
use Jasny\DB\Entity\Validation;
use Jasny\DB\Entity\Meta;
use Jasny\ValidationResult;

/**
 * Something to be performed by an Actor.
 */
class Action extends BasicEntity implements Meta, Validation, Dynamic
{
    use DeepClone;
    use Meta\Implementation;

    /**
     * @var string
     */
    public $schema;

    /**
     * @var string
     */
    public $key;

    /**
     * Action title.
     * @var string|DataInstruction
     */
    public $title;

    /**
     * Description for choosing this action (typically displayed as a button).
     * @var string|DataInstruction
     */
    public $label;

    /**
     * Description of the current action.
     * @var string|DataInstruction
     */
    public $description;

    /**
     * Key of the actor(s).
     * @var string|string[]|null
     */
    public $actor;

    /**
     * Condition that needs to be met to allow the action to be executed.
     * @var bool|DataInstruction
     */
    public $condition = true;

    /**
     * Available responses on the action.
     * @var AvailableResponse[]|AssocEntitySet
     */
    public $responses = [];

    /**
     * Default response used for golden flow
     * @var string|DataInstruction|null
     */
    public $default_response = 'ok';


    /**
     * Validates the action
     *
     * @return ValidationResult
     */
    public function validate(): ValidationResult
    {
        $validation = new ValidationResult();
        $validation->add($this->validateResponses());

        return $validation;
    }

    /**
     * @return ValidationResult
     */
    protected function validateResponses(): ValidationResult
    {
        $validation = new ValidationResult();

        if (count($this->responses) === 0 && ($this->default_response === 'ok' || $this->default_response === null)) {
            return $validation;
        }

        if (!isset($this->responses[$this->default_response])) {
            $validation->addError("Action doesn't have a '%s' response.", $this->default_response);
        }

        $responsePrefix = $validation->translate("'%s' response");
        foreach ($this->responses as $key => $response) {
            $validation->add($response->validate(), sprintf($responsePrefix, $key));
        }
    }

    /**
     * Check if the action contains the response given
     *
     * @param string $key
     * @return bool
     */
    public function isValidResponse(string $key): bool
    {
        return isset($this->responses[$key]);
    }

    /**
     * Get the response specified by the key.
     *
     * @param string $key
     * @return AvailableResponse
     * @throws OutOfBoundsException
     */
    public function getResponse(string $key): AvailableResponse
    {
        if ($key === '') {
            throw new InvalidArgumentException("Key should not be empty");
        }

        if (!isset($this->responses[$key])) {
            throw new OutOfBoundsException("$this doesn't have a '$key' response");
        }

        return $this->responses[$key];
    }

    /**
     * Get the default response of the action.
     *
     * @return AvailableResponse
     */
    public function getDefaultResponse(): ?AvailableResponse
    {
        if ($this->default_response === null) {
            return null;
        }

        if (empty($this->responses) && $this->default_response === 'ok') {
            return new Response();
        }

        return $this->getResponse($this->default_response);
    }
}
