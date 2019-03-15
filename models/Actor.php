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
    public $schema = 'https://specs.livecontracts.io/v0.2.0/asset/actor.json#';

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
     * The identity
     * @var Identity
     */
    public $identity;


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
            ($this->identity !== null ? "actor with " . $this->identity->describe() : null) ??
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
            ($actor->key !== null || $actor->identity !== null) && // Match at least one of these
            ($actor->key === null || $actor->key === $this->key) &&
            ($actor->identity === null || ($this->identity !== null && $this->identity->matches($actor->identity)));
    }
}
