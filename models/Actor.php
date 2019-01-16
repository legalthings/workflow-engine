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
    public $schema;

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
     * @var stdClass
     */
    public $signkeys;


    /**
     * Validate actor.
     *
     * @return ValidationResult
     */
    public function validate(): ValidationResult
    {
        return ValidationResult::success();
    }
}
