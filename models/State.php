<?php

use Jasny\DB\Data;
use Jasny\DB\Entity\Dynamic;
use Jasny\DB\Entity\Validation;
use Jasny\DB\Entity\Meta;
use Jasny\DB\EntitySet;
use Jasny\DB\Entity\Identifiable;
use function Jasny\object_get_properties;

/**
 * Definition of state a process can be in
 */
class State extends BasicEntity implements Dynamic, Meta, Validation
{
    use Meta\Implementation {
        cast as private metaCast;
    }
    use Validation\MetaImplementation;

    /**
     * Short title
     * @var string|DataInstruction
     */
    public $title;
    
    /**
     * Description of the state
     * @var string|DataInstruction
     */
    public $description;
    
    /**
     * Alternative descriptions per actor
     * @var string[]|DataInstruction[]
     */
    public $instructions = [];
    
    /**
     * Set of possible actions in this state
     * @var string[]
     */
    public $actions = [];
    
    /**
     * Reference to the default action (golden flow)
     * @var string|DataInstruction
     */
    public $default_action;
    
    /**
     * Set of state transitions resulting from an action response
     * @var StateTransition[]|\Jasny\DB\EntitySet
     */
    public $transitions = [];
    
    /**
     * State timeout as date period
     * @var string|DataInstruction
     */
    public $timeout;

    /**
     * Flags whether the action should be displayed or not
     * @var boolean
     */
    public $display = true;
    
    /**
     * Class constructor
     */
    public function __construct()
    {
        $this->cast();
    }
    
    
    /**
     * Check whether the state has an allowed action with the given action key
     * 
     * @param string $key
     * @return boolean
     */
    public function hasAllowedAction($key)
    {
        return in_array($this->actions, $key, true);
    }

    /**
     * Get the transition based on the given action and response
     * 
     * @param string $action
     * @param string $response
     * @return string|null
     */
    public function getTransition(string $action, string $response)
    {
        if (!isset($this->transitions) || empty($this->transitions) || $this->transitions->count() === 0) {
            return;
        }
        
        foreach ($this->transitions as $transition) {
            if ((isset($transition->action) && $transition->action !== $action) ||
                (isset($transition->response) && $transition->response !== $response)) {
                continue;
            }
            
            if (!isset($transition->condition) || filter_var($transition->condition, FILTER_VALIDATE_BOOLEAN)) {
                return $transition->transition;
            }
        }
    }

    /**
     * Cast entity properties
     * 
     * @return $this
     */
    public function cast()
    {
        if (is_array($this->transitions)) {
            $this->transitions = EntitySet::forClass(
                StateTransition::class,
                $this->transitions,
                EntitySet::ALLOW_DUPLICATES
            );
        }

        $this->metaCast();

        return $this;
    }

    
    /**
     * Get the data to store in the DB
     *
     * @param array $opts
     * @return array
     */
    public function toData(array $opts = [])
    {
        $data = object_get_properties($this);
        
        foreach ($data as $key => &$item) {
            if ($item instanceof Identifiable) {
                $item = $item->getId();
            } elseif ($item instanceof Data) {
                $item = $item->toData($opts);
            }
        }
        
        foreach ($data as $key => &$value) {
            if (!isset($value)) unset($data[$key]);
        }

        return $data;
    }
    
    
    /**
     * Prepare json serialization
     *
     * @return object
     */
    public function jsonSerialize()
    {
        $object = parent::jsonSerialize();

        $baseProperties = array_keys(get_object_vars(get_class($this)));

        foreach ($baseProperties as $key) {
            if (!isset($object->$key)) {
                unset($object->$key);
            }
        }
                
        return $object;
    }
}
