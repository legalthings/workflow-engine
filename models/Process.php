<?php declare(strict_types=1);

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
    public $schema = 'https://specs.livecontracts.io/v1.0.0/process/schema.json#';

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
     * @var NextState[]|\Jasny\DB\EntitySet|null
     */
    public $next;

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
     * Meta information. Can be specified by each node and isn't shared.
     * @var Meta
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
        $this->setDispatcher(new EventDispatcher()); // Empty dispatcher, acts as null object.

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
        if ($this->next !== null && !$this->next instanceof EntitySet) {
            // Should not really contain duplicates, but entity is not identifiable.
            $this->next = EntitySet::forClass(NextState::class, $this->next, 0, EntitySet::ALLOW_DUPLICATES);
        }

        if (!$this->previous instanceof EntitySet) {
            // Should not really contain duplicates, but entity is not identifiable.
            $this->previous = EntitySet::forClass(Response::class, $this->previous, 0, EntitySet::ALLOW_DUPLICATES);
        }

        parent::cast();

        $this->dispatcher->trigger('cast', $this);

        return $this;
    }


    /**
     * Find any actors that matches the given one.
     *
     * @param string|Actor $match  Actor or actor key.
     * @return Pipeline
     */
    protected function getMatchingActors($match): Pipeline
    {
        $find = $match instanceof Actor ? $match : (new Actor)->set('key', $match);

        return Pipeline::with($this->actors)
            ->filter(function(Actor $actor) use ($find) {
                return $actor->matches($find);
            });
    }

    /**
     * Check if the process has the specific actor.
     *
     * @param string|Actor $match  Actor or actor key.
     * @return bool
     */
    public function hasActor($match): bool
    {
        return $this->getMatchingActors($match)->count() > 0;
    }

    /**
     * Get the specific actor or an actor that matches the condition.
     *
     * @param string|Actor $match  Actor or actor key.
     * @return Actor
     * @throws OutOfBoundsException
     */
    public function getActor($match): Actor
    {
        return $this->getMatchingActors($match, true)->first();
    }

    /**
     * Get an actor that is allowed to perform the specified action.
     * If there actor exists, but isn't allowed to execute the action, return null.
     *
     * @param string       $actionKey
     * @param string|Actor $actor     Actor or actor key.
     * @return Actor|null
     * @throws OutOfBoundsException
     */
    public function getActorForAction(string $actionKey, $match): ?Actor
    {
        $required = true;
        $action = $this->getAvailableAction($actionKey);

        return $this->getMatchingActors($match, true)
            ->apply(function() use (&$required) {
                $required = false;
            })
            ->filter(function(Actor $actor) use ($action) {
                return $action->isAllowedBy($actor);
            })
            ->first($required);
    }

    /**
     * Get an action that is available in the current state.
     *
     * @param string $actionKey
     * @return Action
     * @throws OutOfBoundsException
     */
    public function getAvailableAction(string $actionKey): Action
    {
        if (isset($this->current->actions[$actionKey])) {
            $msg = "Action '%s' is not available in state '%s' for process '%s";
            throw new OutOfBoundsException(sprintf($msg, $actionKey, $this->current->key, $this->id));
        }

        return $this->current->actions[$actionKey];
    }


    /**
     * Checks if the process is finished
     *
     * @return bool
     */
    public function isFinished(): bool
    {
        return in_array($this->current->key, Process::getFinishedStates(), true);
    }


    /**
     * Validate the process
     *
     * @return ValidationResult
     */
    public function validate(): ValidationResult
    {
        $validation = parent::validate();

        foreach ($this->actors as $actor) {
            $validation->add($actor->validate(), $actor->describe());
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
     * Get the states where the process is finished.
     *
     * @return array
     */
    public static function getFinishedStates()
    {
        return [':success', ':failed', ':cancelled'];
    }
}
