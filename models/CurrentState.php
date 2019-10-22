<?php

declare(strict_types=1);

use Improved as i;
use Jasny\DB\EntitySet;
use Carbon\CarbonImmutable;

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
     * @var DateTimeImmutable|null
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
     * Actor who performed current action
     * @var Actor
     */
    public $actor;

    /**
     * Cast properties
     *
     * @return $this
     */
    public function cast()
    {
        if ($this->due_date instanceof DateTime) {
            $this->due_date = CarbonImmutable::createFromMutable($this->due_date);
        }

        return parent::cast();
    }

    /**
     * {@inheritdoc}
     */
    public function toData(array $opts = []): array
    {
        $data = parent::toData($opts);

        if (isset($data['due_date']) && $data['due_date'] instanceof DateTimeImmutable) {
            $date = $data['due_date']->format(DateTime::ISO8601);
            $data['due_date'] = DateTime::createFromFormat(DateTime::ISO8601, $date);
        }

        return $data;
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
     * @param Response $response
     * @return StateTransition|null
     */
    public function getTransition(Response $response): ?StateTransition
    {
        return i\iterable_find($this->transitions, function(StateTransition $transition) use ($response) {
            return $transition->appliesTo($response) && $transition->meetsCondition();
        });
    }
}
