<?php

use Jasny\DB\Entity\Meta;

/**
 * A response given response.
 */
class Response extends BasicEntity implements Meta
{
    use DeepClone;
    use Meta\Implementation;

    /**
     * @var string
     */
    public $schema;

    /**
     * The title that will be displayed in the process if the action is performed.
     * @var mixed
     */
    public $title;

    /**
     * Show the response.
     * @var string
     * @options never, once, always
     */
    public $display = 'always';

    /**
     * Action that was performed.
     * @var Action
     */
    public $action;

    /**
     * Response key within the action
     * @var string
     */
    public $key;

    /**
     * Actor that performed the action.
     * @var Actor
     */
    public $actor;
    
    /**
     * Response data
     * @var mixed
     */
    public $data;
    
    /**
     * Anchoring receipt
     * @var stdClass
     */
    public $receipt;
}
