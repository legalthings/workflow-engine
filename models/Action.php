<?php

use Jasny\DB\BasicEntity;
use Jasny\DB\Entity\Validation;
use Jasny\DB\Entity\Meta;
use Jasny\ValidationResult;

/**
 * Something to be performed by an Actor.
 */
class Action extends BasicEntity implements Meta, Validation
{
    use DeepClone;
    use Meta\Implementation;

    /**
     * @var string
     */
    public $schema;

    /**
     * Action title
     * @var string|DataInstruction
     */
    public $title;

    /**
     * Description that is seen by the current actor
     * @var string|DataInstruction
     */
    public $label;

    /**
     * Description that is seen by anyone that is not the current actor
     * @var string|DataInstruction
     */
    public $description;

    /**
     * Key of the actor(s)
     * @var string|string[]|null
     */
    public $actor;

    /**
     * Responses on the action.
     * @var ActionResponse[]|AssocEntitySet
     */
    public $responses = [];

    /**
     * Default response used for golden flow
     * @var string|DataInstruction|null
     */
    public $default_response = 'ok';

    /**
     * Flags whether the action should be displayed or not
     * @var string
     * @options always,once,never
     */
    public $display = 'always';


    /**
     * Class constructor
     */
    public function __construct()
    {
        $this->cast();
    }

    /**
     * Set values
     *
     * @param array $values
     * @return $this
     */
    public function setValues($values)
    {
        if (isset($values['$schema'])) {
            $values['schema'] = $values['$schema'];
            unset($values['$schema']);
        }

        parent::setValues($values);

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

        if ($this->default_response !== 'ok' && !isset($this->responses[$this->default_response])) {
            $validation->addError("Action doesn't have a '%s' response.", $this->default_response);
        }

        $responsePrefix = $validation->translate("'%s' response");
        foreach ($this->responses as $key => $response) {
            $validation->add($response->validate(), sprintf($responsePrefix, $key));
        }

        return $validation;
    }

    /**
     * Check if the action contains the response given
     *
     * @param string $key
     * @return boolean
     */
    public function isValidResponse(string $key)
    {
        return isset($this->responses[$key]);
    }

    /**
     * Get the response specified by the key.
     *
     * @param string $key
     * @return Response
     * @throws OutOfBoundsException
     */
    public function getResponse(string $key)
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
     * Get the default response of the action
     *
     * @return Response|null
     */
    public function getDefaultResponse()
    {
        $key = $this->default_response;

        if (!isset($this->responses[$key])) {
            if ($key !== 'ok') {
                trigger_error("Action doesn't have a '$key' response.", E_USER_WARNING);
            }

            return new Response();
        }

        return $this->responses[$key];
    }
}
