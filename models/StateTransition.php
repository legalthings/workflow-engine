<?php

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
     * @var string
     */
    public $schema = 'https://specs.livecontracts.io/v1.0.0/state/schema.json#transition';

    /**
     * Action reference
     * @var string
     */
    public $action;
    
    /**
     * Action response
     * @var string
     */
    public $response;
    
    /**
     * Condition that must be true
     * @var boolean|DataInstruction
     */
    public $condition = true;
    
    /**
     * Reference of the state to transition to
     * @var string
     */
    public $transition;


    /**
     * Validate the state transition.
     *
     * @return ValidationResult
     */
    public function validate(): ValidationResult
    {
        return ValidationResult::success();
    }

    /**
     * Class constructor
     */
    public function __construct()
    {
        $this->cast();
    }
}
