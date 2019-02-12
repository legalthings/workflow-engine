<?php

use Jasny\DB\Entity\Dynamic;
use Jasny\DB\Entity\Validation;
use Jasny\DB\Entity\Meta;
use Jasny\ValidationResult;

/**
 * A person or program in a process which performs some action.
 */
class Actor extends BasicEntity implements Meta, Validation, Dynamic
{
    use DeepClone;
    use Meta\Implementation;

    /**
     * @var string
     */
    public $schema = 'https://specs.livecontracts.io/v1.0.0/asset/actor.json#';

    /**
     * @var string
     */
    public $key;

    /**
     * Title as defined in the scenario.
     * @var string
     */
    public $title;

    /**
     * The user id
     * @var string|\stdClass
     */
    public $id;

    /**
     * Actor's name
     * @var string
     */
    public $name;

    /**
     * Actor's email address
     * @var string
     */
    public $email;

    /**
     * Public keys of the identity
     * @var array
     */
    public $signkeys = [];


    /**
     * Validate actor.
     *
     * @return ValidationResult
     */
    public function validate(): ValidationResult
    {
        return ValidationResult::success();
    }

    /**
     * Describe the actor based on the known properties.
     *
     * @return string
     */
    public function describe(): string
    {
        return
            $this->title ??
            ($this->key !== null ? "actor '{$this->key}'" : null) ??
            ($this->id !== null ? "actor with id '{$this->id}'" : null) ??
            ($this->signkeys !== [] ? "actor with signkey '" . join("', '", $this->signkeys) . "'" : null) ??
            'an unknown actor';
    }

    /**
     * See if the specified actor matches this actor.
     *
     * @param Actor $actor  Only some properties need to be set.
     * @return bool
     */
    public function matches(Actor $actor): bool
    {
        return
            ($actor->key !== null || $actor->id !== null || $actor->signkeys !== []) && // Match at least one of these
            ($actor->key === null || $actor->key === $this->key) &&
            ($actor->id === null || $actor->id === $this->id) &&
            ($actor->signkeys === [] || array_contains($this->signkeys, $actor->signkeys, true));
    }
}
