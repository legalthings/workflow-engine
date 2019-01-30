<?php declare(strict_types=1);

use Improved as i;
use Jasny\DB\EntitySet;

/**
 * The current state a process is in.
 */
class CurrentState extends State
{
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
     * State timeout
     * @var DateTime
     */
    public $due_date;

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


    /**
     * Cast properties
     *
     * @return $this
     */
    public function cast()
    {
        if ($this->transitions !== null && !$this->transitions instanceof EntitySet) {
            // Should not really contain duplicates, but entity is not identifiable.
            $this->transitions = EntitySet::forClass(
                StateTransition::class,
                $this->transitions,
                0,
                EntitySet::ALLOW_DUPLICATES
            );
        }

        return parent::cast();
    }

    /**
     * Get the default action for this state.
     *
     * @return Action|null
     */
    public function getDefaultAction(): ?Action
    {
        return i\iterable_find($this->actions, function (Action $action): bool {
            return is_scalar($action->condition) && (bool)$action->condition;
        });
    }

    /**
     * Get the transition for a given response.
     *
     * @param string $action    Action key
     * @param string $response  Response key
     * @return StateTransition|null
     */
    public function getTransition(string $action, string $response): ?StateTransition
    {
        return i\iterable_find($this->transitions, function(StateTransition $transition) use ($action, $response) {
            return
                (!isset($transition->action) || $transition->action === $action) &&
                (!isset($transition->response) || $transition->response === $response) &&
                (!isset($transition->condition) || (bool)$transition->condition === true);
        });
    }
}
