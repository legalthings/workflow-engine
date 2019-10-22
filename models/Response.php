<?php
declare(strict_types=1);

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
     * Response constructor.
     *
     * @param Action|null $action
     * @param string|null key
     */
    public function __construct(?Action $action = null, ?string $key = null)
    {
        parent::__construct();

        if ($action !== null) {
            $key === null || $action->getResponse($key); // Assert that action has response.

            $this->action = $action;
            $this->key = $key ?? $action->default_response;
        }
    }

    /**
     * Cast all properties.
     *
     * @return $this
     */
    public function cast()
    {
        if (is_string($this->action)) {
            $this->action = (new Action())->setValues(['key' => $this->action]);
        }

        if (is_string($this->actor)) {
            $this->actor = (new Actor())->setValues(['key' => $this->actor]);
        }

        if (!isset($this->key)) {
            $this->key = 'ok';
        }

        return parent::cast();
    }

    /**
     * Get response reference; action and response key.
     *
     * @return string
     */
    public function getRef(): string
    {
        return $this->action->key . ($this->key !== null ? '.' . $this->key : '');
    }
}
