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
    public $schema = 'https://specs.livecontracts.io/v1.0.0/response/schema.json#';

    /**
     * The title that will be displayed in the process if the action is performed.
     * @var string
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


    /**
     * Cast all properties.
     *
     * @return $this
     */
    public function cast()
    {
        if (is_string($this->action)) {
            $this->action = (new Action)->setValues(['key' => $this->action]);
        }

        if (is_string($this->actor)) {
            $this->actor = (new Actor)->setValues(['key' => $this->actor]);
        }

        return parent::cast();
    }
}
