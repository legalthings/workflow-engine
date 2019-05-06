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
     * @immutable
     */
    public $schema = 'https://specs.livecontracts.io/v0.2.0/process/schema.json#';

    /**
     * @var string
     * @immutable
     */
    public $id;

    /**
     * The title of the process.
     * @var string
     * @immutable
     */
    public $title;

    /**
     * Scenario that the process is based on.
     * @var Scenario
     * @immutable
     */
    public $scenario;

    /**
     * Event chain id.
     * @var string|null
     * @immutable
     */
    public $chain;

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

        if (!is_scalar($this->chain) && $this->chain !== null) {
            $this->chain = is_object($this->chain) ? $this->chain->id : $this->chain['id'];
        }

        parent::cast();

        if ($this->dispatcher !== null) {
            $this->dispatcher->trigger('cast', $this);
        }

        return $this;
    }

    /**
     * Set the values of process
     *
     * @param array|\stdClass $values
     * @return self
     */
    public function setValues($values)
    {
        if (!$this->actors instanceof AssocEntitySet) {
            return parent::setValues($values);
        }

        $values = arrayify($values);

        $actorsValues = null;
        if (isset($values['actors']) && is_array($values['actors'])) {
            $actorsValues = $values['actors'] ?? [];
            unset($values['actors']);
        }

        parent::setValues($values);

        if (isset($actorsValues)) {
            $this->updateActors($actorsValues);
        }

        return $this;
    }

    /**
     * Update current process actors
     *
     * @param array $actorsValues
     */
    protected function updateActors(array $actorsValues): void
    {
        Pipeline::with($actorsValues)
            ->mapKeys(static function($vals, $key) {
                return $vals['key'] ?? $key;
            })
            ->filter(function ($_, $key) {
                return isset($this->actors[$key]);
            })
            ->map(static function($vals) {
                return is_string($vals) ? ['identity' => $vals] : (array)$vals;
            })
            ->apply(function(array $vals, $key) {
                $this->actors[$key]->setValues($vals);
            })
            ->walk();
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
     * Check if the process has any actor with an identity.
     *
     * @return bool
     */
    public function hasKnownActors(): bool
    {
        return i\iterable_has_any($this->actors, static function(Actor $actor) {
            return $actor->identity !== null;
        });
    }

    /**
     * Get the specific actor or an actor that matches the condition.
     *
     * @param string|Actor $match  Actor or actor key.
     * @return Actor
     * @throws RangeException
     */
    public function getActor($match): Actor
    {
        return $this->getMatchingActors($match)->first(true);
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
        $action = $this->getAvailableAction($actionKey);

        return $this->getMatchingActors($match)
            ->then(function(iterable $actors): Generator {
                $any = false;

                foreach ($actors as $actor) {
                    $any = true;
                    yield $actor;
                }

                if (!$any) {
                    throw new OutOfBoundsException('Actor not found');
                }
            })
            ->filter(function(Actor $actor) use ($action) {
                return $action->isAllowedBy($actor);
            })
            ->first();
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
        if (!isset($this->current->actions[$actionKey])) {
            $msg = "Action '%s' is not available in state '%s' for process '%s'";
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

        if (!$this->hasKnownActors()) {
            $validation->addError('no known actors');
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
        $count = count($this->previous);
        
        return $count > 0 ? 
            $this->previous[$count - 1] :
            null;
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
    
    /**
     * Prepare json serialization
     *
     * @return stdClass
     */
    public function jsonSerialize(): stdClass
    {
        $object = parent::jsonSerialize();
        $object = object_rename_key($object, 'schema', '$schema');

        return $object;
    }
}
