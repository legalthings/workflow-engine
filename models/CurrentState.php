<?php

/**
 * The current state a process is in.
 */
class CurrentState extends State
{
    /**
     * @var string
     */
    public $key;

    /**
     * Short title
     * @var string
     */
    public $title;
    
    /**
     * Description of the state
     * @var string
     */
    public $description;
    
    /**
     * Alternative descriptions per actor
     * @var string[]
     */
    public $instructions = [];
    
    /**
     * Set of possible actions in this state
     * @var Action[]|AssocEntitySet
     */
    public $actions = [];
    
    /**
     * Set of state transitions resulting from an action response
     * @var StateTransition[]|\Jasny\DB\EntitySet
     */
    public $transitions = [];
    
    /**
     * State timeout as date period
     * @var string
     */
    public $timeout;

    /**
     * Flags whether the state should be displayed or not
     * @var string
     * @options always,once,never
     */
    public $display = 'always';

    /**
     * The response given for the current state.
     * This is only set during a state transition.
     * @var Response|null
     */
    public $response;
}
