<?php

use Improved as i;
use Improved\IteratorPipeline\Pipeline;
use Jasny\DB\Entity\Dynamic;
use Jasny\EventDispatcher\EventDispatcher;
use Jasny\ValidationResult;
use Jasny\ValidationException;

/**
 * A scenario is the blueprint of a process.
 *
 * {@internal The whole scenario is immutable, no need to specify that per property.}}
 */
class Scenario extends MongoDocument implements Dynamic
{
    use DeepClone;

    /**
     * @var string
     */
    public $schema = 'https://specs.livecontracts.io/v0.2.0/scenario/schema.json#';
    
    /**
     * @var string
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
     * @var Asset[]|AssocEntitySet
     */
    public $definitions = [];

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
    final public function setDispatcher(EventDispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * Dispatch an event for the scenario.
     *
     * @param string $event
     * @param mixed  $payload
     * @return mixed
     */
    final public function dispatch(string $event, $payload = null)
    {
        return $this->dispatcher->trigger($event, $this, $payload);
    }


    /**
     * Cast the properties of the Scenario object.
     *
     * @return $this
     */
    public function cast()
    {
        parent::cast();

        $this->castJsonSchemas($this->actors);
        $this->castJsonSchemas($this->assets);

        // Add implicit states
        foreach ([':success', ':failed', ':cancelled'] as $key) {
            if (!isset($this->states[$key])) {
                $this->states[$key] = new State();
            }
        }

        if ($this->dispatcher !== null) {
            $this->dispatcher->trigger('cast', $this);
        }

        return $this;
    }

    /**
     * Make sure the type is set for each JSON Schema.
     *
     * @param iterable $set
     */
    protected function castJsonSchemas(iterable $set): void
    {
        foreach ($set as $jsonSchema) {
            if ($jsonSchema->type === null) {
                $jsonSchema->type = 'object';
                $jsonSchema->additionalProperties = true;
            };
        }
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
     * Get the available actions for the given state.
     *
     * @param State|string $state
     * @return AssocEntitySet&iterable<Action>
     */
    public function getActionsForState($state): AssocEntitySet
    {
        i\type_check($state, ['string', State::class]);
        $state = is_string($state) ? $this->getState($state) : $state;

        $actions = Pipeline::with(array_merge($state->actions, $this->allow_actions))
            ->unique()
            ->map(function($name) {
                return $this->actions[$name] ?? null;
            })
            ->cleanup();

        return AssocEntitySet::forClass(Action::class, $actions);
    }

    /**
     * Validates the scenario.
     *
     * @return ValidationResult
     */
    public function validate(): ValidationResult
    {
        $validation = new ValidationResult();

        $isSchemaValid = is_schema_link_valid($this->schema, 'scenario');
        if (!$isSchemaValid) {
            $validation->addError("schema property value is not valid");
        }

        $actionPrefix = $validation->translate("action '%s'");
        foreach ($this->actions as $key => $action) {
            $validation->add($action->validate(), sprintf($actionPrefix, $key) . ':');
        }
        
        if (!isset($this->states['initial'])) {
            $validation->addError("scenario must have an 'initial' state");
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
    public function setValues($values): self
    {
        $values = array_rename_key((array)$values, '$schema', 'schema');

        if ($this->dispatcher !== null) {
            $values = $this->dispatcher->trigger('setValues', $this, $values);
        }

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
     * Prepare json serialization
     *
     * @return stdClass
     */
    public function jsonSerialize(): stdClass
    {
        $object = parent::jsonSerialize();
        $object = object_rename_key($object, 'schema', '$schema');

        // Remove implicit states
        $object->states = (object)Pipeline::with((array)$object->states->jsonSerialize())
            ->filter(function(stdClass $state, string $key) {
                return !str_starts_with($key, ':');
            })
            ->toArray();

        return $object;
    }
}
