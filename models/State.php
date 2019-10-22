<?php
declare(strict_types=1);

use Improved\IteratorPipeline\Pipeline;
use Jasny\DB\Data;
use Jasny\DB\Entity\Validation;
use Jasny\DB\EntitySet;
use Jasny\ValidationResult;
use function Jasny\object_get_properties;

/**
 * Definition of state a process can be in.
 */
class State extends BasicEntity implements Validation
{
    use DeepClone;

    /**
     * @var string
     */
    public $key;

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
     * Set of state transitions resulting from an action response
     * @var StateTransition[]|\Jasny\DB\EntitySet
     */
    public $transitions = [];
    
    /**
     * State timeout as ISO 8601 date duration.
     * @see http://en.wikipedia.org/wiki/Iso8601#Durations
     *
     * @pattern ^P(\d+Y)?(\d+M)?(\d+D)?(T(\d+H)?(\d+M)?(\d+S)?)?$
     * @var string|DataInstruction
     */
    public $timeout;

    /**
     * Flags whether the state should be displayed or not when showing the next states.
     * @var string
     * @options always,once,never
     */
    public $display = 'always';

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
                null,
                EntitySet::ALLOW_DUPLICATES
            );
        }

        return parent::cast();
    }

    /**
     * Get a list of actions
     * @return string[]
     */
    public function getActions(): array
    {
        return Pipeline::with($this->transitions)
            ->map(static function (StateTransition $transition) {
                return $transition->getActionKey();
            })
            ->filter(static function (string $action) {
                return $action !== '*';
            })
            ->unique()
            ->toArray();
    }

    /**
     * @return ValidationResult
     */
    public function validate(): ValidationResult
    {
        return ValidationResult::success();
    }

    /**
     * Check if this is a final state.
     *
     * @return bool
     */
    public function isFinal(): bool
    {
        return count($this->actions) === 0;
    }

    /**
     * Get the data to store in the DB
     *
     * @param array $opts
     * @return array
     */
    public function toData(array $opts = []): array
    {
        $data = object_get_properties($this);

        foreach ($data as $key => &$item) {
            if ($item instanceof Data) {
                $item = $item->toData($opts);
            }
        }
        
        foreach ($data as $key => &$value) {
            if (!isset($value)) unset($data[$key]);
        }

        return $data;
    }
}
