<?php declare(strict_types=1);

use Improved as i;

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
     * State timeout as date period
     * @var string
     */
    public $timeout;

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
     * Get the default action for this state.
     *
     * @return Action|null
     */
    public function getDefaultAction(): ?Action
    {
        return i\iterable_find($this->actions, function (Action $action, $key): bool {
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
