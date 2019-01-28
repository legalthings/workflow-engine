<?php

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
     * Description of the action.
     * @var string|DataInstruction
     */
    public $description;

    /**
     * Key of the actor(s) that are allowed the perform the action.
     * @var string[]|DataInstruction
     */
    public $actors;

    /**
     * Condition that needs to be met to allow the action to be executed.
     * @var bool|DataInstruction
     */
    public $condition = true;

    /**
     * Available responses on the action.
     * @var AvailableResponse[]|AssocEntitySet
     */
    public $responses;

    /**
     * Default response used for golden flow
     * @var string|null
     */
    public $default_response = 'ok';

    /**
     * Action constructor.
     */
    public function __construct()
    {
        parent::__construct();

        if (!isset($this->responses)) {
            $this->responses = AssocEntitySet::forClass(AvailableResponse::class);
            $this->responses['ok'] = new AvailableResponse();
        }
    }

    /**
     * Set values
     *
     * @param array $values
     * @return $this
     */
    public function setValues($values)
    {
        if (isset($values['actor'])) {
            $values['actors'] = [$values['actor']];
            unset($values['actor']);
        }

        $responseValues = array_only($values, ['display', 'update']);
        $actionValues = array_without($values, ['display', 'update']);

        parent::setValues($actionValues);

        if ($responseValues !== []) {
            foreach ($this->responses as $response) {
                $response->setValues($responseValues);
            }
        }

        return $this;
    }

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
     * Check if the given actor may perform this action.
     *
     * @param Actor|string $actor
     * @return bool
     */
    public function isAllowedBy($actor): bool
    {
        $key = $actor instanceof Actor ? $actor->key : $actor;

        return in_array($key, $this->actors, true);
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
}