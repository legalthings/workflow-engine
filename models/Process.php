<?php

use Improved as i;
use Improved\IteratorPipeline\Pipeline;
use Jasny\DB\EntitySet;
use Jasny\ValidationResult;
use Ramsey\Uuid\Uuid;
use Jasny\EventDispatcher\EventDispatcher;

/**
 * Representation of the current state of a running process.
 */
class Process extends MongoDocument
{
    /**
     * @var string
     */
    public $schema;

    /**
     * @var string
     */
    public $id;

    /**
     * The title of the process.
     * 
     * @var string
     */
    public $title;

    /**
     * Scenario id
     * @var Scenario
     */
    public $scenario;

    /**
     * @var Actor[]|AssocEntitySet
     */
    public $actors = [];

    /**
     * @var Response[]|\Jasny\DB\EntitySet
     */
    public $previous = [];

    /**
     * @var CurrentState
     */
    public $current;
    
    /**
     * The next states in the process only considering the default actions and responses.
     * @var NextState[]|\Jasny\DB\EntitySet
     */
    public $next = [];

    /**
     * Process info
     * @var Asset
     */
    public $info = [];

    /**
     * A list of process assets
     * @var Asset[]|AssocEntitySet
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

        if (!isset($this->id)) {
            $this->id = Uuid::uuid4()->toString();
        }

        parent::__construct();
    }

    /**
     * Set the event dispatcher. Should only be called by the ProcessGateway.
     * @internal
     *
     * @param EventDispatcher $dispatcher
     */
    final public function setDispatcher(EventDispatcher $dispatcher): void
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * Dispatch an event for the process.
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
     * Cast properties
     *
     * @return $this
     */
    public function cast(): self
    {
        if (!$this->next instanceof EntitySet) {
            // Should not really contain duplicates, but entity is not identifiable.
            $this->next = EntitySet::forClass(NextState::class, $this->next, EntitySet::ALLOW_DUPLICATES);
        }

        parent::cast();

        $this->dispatcher->trigger('cast', $this);

        return $this;
    }

    /**
     * Get a specific actor
     *
     * @param string $key
     * @return Actor
     * @throws OutOfBoundsException
     */
    public function getActor($key): Actor
    {
        if ($key instanceof Actor) {
            $key = $key->getKey();
        }

        if (!isset($this->actors[$key])) {
            throw new OutOfBoundsException("process doesn't have a '$key' actor");
        }

        return $this->actors[$key];
    }

    /**
     * Checks if the process is finished
     *
     * @return bool
     */
    public function isFinished(): bool
    {
        return in_array($this->current->getKey(), Process::getFinishedStates(), true);
    }


    /**
     * Validate the process
     *
     * @return ValidationResult
     */
    public function validate(): ValidationResult
    {
        $validation = parent::validate();

        $prefix = $validation->translate("actor '%s'");
        foreach ($this->actors as $actor) {
            $validation->add($actor->validate(), sprintf($prefix, $actor->title));
        }

        $validation = $this->dispatcher->trigger('validate', $this, $validation);

        return $validation;
    }


    /**
     * Get the previous response (single) if it exists.
     *
     * @return Response|null
     */
    public function getPreviousResponse(): ?Response
    {
        $length = count($this->previous);
        
        if (!$length) {
            return null;
        }
        
        return $this->previous[$length - 1];
    }
    
    /**
     * Get the actions in the current state that can be executed by the given actor.
     *
     * @param string|Actor $actor  May be null if anyone may access the action
     * @return EntitySet&iterable<Action>
     */
    public function getAvailableActions($actor = null): iterable
    {
        if ($actor === null) {
            return $this->current->actions;
        }

        $actor = is_string($actor) ? $this->getActor($actor) : $actor;

        $actions = i\iterable_filter($this->current->action, function ($action) use ($actor) {
            $action->isAllowedBy($actor);
        });

        return EntitySet::forClass(Action::class, $actions);
    }

    /**
     * Get the states where the process is finished.
     *
     * @return array
     */
    public static function getFinishedStates()
    {
        return [':success', ':failed', ':cancelled'];
    }
}
