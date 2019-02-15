<?php

use Jasny\DB\Entity\Dynamic;
use Jasny\DB\Entity\Meta;

/**
 * A person or program in a process which performs some action.
 *
 * {@internal Validation is purely done via JSONSchema.}}
 */
class Actor extends BasicEntity implements Meta, Dynamic
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
     * The identity reference.
     * @var string|\stdClass
     */
    public $identity;

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
     * Describe the actor based on the known properties.
     *
     * @return string
     */
    public function describe(): string
    {
        return
            $this->title ??
            ($this->key !== null ? "actor '{$this->key}'" : null) ??
            ($this->identity !== null ? "actor with identity '{$this->identity}'" : null) ??
            ($this->signkeys !== [] ? "actor with signkey '" . join("'/'", $this->signkeys) . "'" : null) ??
            'unknown actor';
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
            ($actor->key !== null || $actor->identity !== null || $actor->signkeys !== []) && // Match at least one of these
            ($actor->key === null || $actor->key === $this->key) &&
            ($actor->identity === null || $actor->identity === $this->identity) &&
            ($actor->signkeys === [] || array_contains($this->signkeys, $actor->signkeys, true));
    }
}
