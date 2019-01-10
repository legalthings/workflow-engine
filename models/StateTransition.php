<?php

use Jasny\DB\Entity\Dynamic;
use Jasny\DB\Entity\Validation;
use Jasny\DB\Entity\Meta;

/**
 * Declaration of a state transition
 */
class StateTransition extends BasicEntity implements Dynamic, Meta, Validation
{
    use Meta\Implementation;
    use Validation\MetaImplementation;

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
    public $condition;
    
    /**
     * Reference of the state to transition to
     * @var string
     * @required
     */
    public $transition;
    
    
    /**
     * Class constructor
     */
    public function __construct()
    {
        $this->cast();
    }
}
