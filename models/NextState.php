<?php declare(strict_types=1);

/**
 * Predicted next state in the process.
 */
class NextState extends BasicEntity
{
    /**
     * @var string
     */
    public $key;

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
     * State timeout as date period
     * @var string
     */
    public $timeout;

    /**
     * Flags whether the state should be displayed or not when showing the next states.
     * @var string
     * @options always,once,never
     */
    public $display = 'always';

    /**
     * Key of the actor(s) that should do the default action for this state.
     * @var string[]
     */
    public $actors;
}
