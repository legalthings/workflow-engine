<?php

use Improved as i;
use Jasny\DB\Entity\Dynamic;
use Jasny\EventDispatcher\EventDispatcher;

/**
 * A scenario is the blueprint of a process.
 */
class Scenario extends MongoDocument implements Dynamic
{
    /**
     * @var string
     * @required
     */
    public $schema;
    
    /**
     * @var string
     * @required
     * @immutable
     */
    public $id;

    /**
     * Title as displayed
     * @var string
     */
    public $title;

    /**
     * Description of the scenario
     * @var string
     */
    public $description;

    /**
     * @var JsonSchema[]|AssocEntitySet
     */
    public $actors = [];

    /**
     * @var Action[]|AssocEntitySet
     */
    public $actions = [];
    
    /**
     * Actions that can be invoked during any state
     * @var string|array
     */
    public $allow_actions = [];

    /**
     * @var State[]|AssocEntitySet
     */
    public $states = [];

    /**
     * @var JsonSchema[]|AssocEntitySet
     */
    public $assets = [];
    
    /**
     * Constant values and predefined objects
     * @var Asset[]|AssetSet
     */
    public $definitions = [];
    
    /**
     * Schema of the process information
     * @var JsonSchema
     */
    public $info;

    /**
     * Meta information. Can be specified by each node and isn't shared.
     * @var object
     */
    public $meta = [];

    /**
     * @var \Jasny\EventDispatcher\EventDispatcher
     * {@internal FQCN instead of only the class name, because of issue with TypeCast}}
     */
    protected $dispatcher;


    /**
     * Class constructor
     */
    public function __construct()
    {
        // Empty dispatcher, acts as null object.
        $this->setDispatcher(new EventDispatcher());

        parent::__construct();
    }

    /**
     * Set the event dispatcher. Should only be called by the ScenarioGateway.
     * @internal
     *
     * @param EventDispatcher $dispatcher
     */
    public function setDispatcher(EventDispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * Cast the properties of the Scenario object.
     *
     * @return $this
     */
    public function cast()
    {
        parent::cast();

        // Add implicit states
        foreach ([':success', ':failed', ':cancelled'] as $key) {
            if (!isset($this->states[$key])) {
                $this->states[$key] = new State();
            }
        }

        if (!isset($this->info)) {
            $this->info = new JsonSchema(['type' => 'object', 'properties' => []]);
        }

        return $this;
    }

    /**
     * Get the LTRI
     *
     * @return string
     */
    public function getLTRI()
    {
        return "lt:/scenarios/" . (string)$this->getId();
    }
    
    /**
     * Get a specific action
     * 
     * @param string $key
     * @return Action
     */
    public function getAction(string $key)
    {
        if (!isset($this->actions[$key])) {
            throw new OutOfBoundsException("Scenario doesn't have a '$key' action");
        }
        
        return $this->actions[$key];
    }
    
    /**
     * Get a specific state
     * 
     * @param string $key
     * @return State
     */
    public function getState($key)
    {
        if (!isset($this->states[$key])) {
            throw new OutOfBoundsException("Scenario doesn't have a '$key' state");
        }
        
        return $this->states[$key];
    }
    
    /**
     * Get a specific actor
     * 
     * @param string $key
     * @return Actor
     */
    public function getActor($key)
    {
        if (!isset($this->actors[$key])) {
            throw new OutOfBoundsException("Scenario doesn't have a '$key' actor");
        }
        
        return $this->actors[$key];
    }
    
    /**
     * Validates the scenario.
     *
     * @return Validation
     */
    public function validate()
    {
        $validation = parent::validate();

        $actorPrefix = $validation->translate("actor '%s'");
        foreach ($this->actors as $key => $actor) {
            $validation->add($actor->validate(), sprintf($actorPrefix, $key) . ':');
        }

        $actionPrefix = $validation->translate("action '%s'");
        foreach ($this->actions as $key => $action) {
            $validation->add($action->validate(), sprintf($actionPrefix, $key) . ':');
        }
        
        if (!isset($this->states[':initial'])) {
            $validation->addError("scenario must have an ':initial' state");
        }
        
        $statePrefix = $validation->translate("state '%s'");
        foreach ($this->states as $key => $state) {
            $validation->add($state->validate(), sprintf($statePrefix, $key) . ':');
        }

        $validation = $this->dispatcher->trigger('validate', $this, $validation);

        return $validation;
    }

    
    /**
     * Set values
     *
     * @param array $values
     * @return $this
     */
    public function setValues($values)
    {
        $values = array_rename_key($values, '$schema', 'schema');

        $values = $this->dispatcher->trigger('setValues', $this, $values);

        return parent::setValues($values);
    }

    /**
     * Convert loaded values to an entity.
     * Calls the constructor *after* setting the properties.
     * 
     * @param stdClass|array $values
     * @return static
     */
    public static function fromData($values)
    {
        $values = (array)self::decodeUnicodeChars($values);

        return parent::fromData($values);
    }
    
    /**
     * Get the data to store in the DB
     *
     * @param array $opts
     * @return array
     */
    public function toData(array $opts = []): array
    {
        $data = parent::toData();
        $data = array_rename_key($data, '$schema', 'schema');

        // Remove implicit states
        $data['states'] = array_values(array_filter($data['states'], function ($state) {
            return !str_starts_with($state['key'], ':') || $state['key'] === ':initial';
        }));

        return $data;
    }

    /**
     * Prepare json serialization
     *
     * @return stdClass
     */
    public function jsonSerialize()
    {
        $values = (array)parent::jsonSerialize();

        array_rename_key($values, 'schema', '$schema');

        // Remove implicit states
        $values['states'] = (object)array_filter(i\iterable_to_array($values['states']), function ($key) {
            return !str_starts_with($key, ':') || $key === ':initial';
        }, ARRAY_FILTER_USE_KEY);

        return (object)i\iterable_to_array(i\iterable_cleanup($values), true);
    }
}
