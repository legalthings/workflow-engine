<?php

use Jasny\DB\Entity\Validation;
use Jasny\DB\Entity\Meta;
use Jasny\DB\EntitySet;
use Jasny\ValidationResult;

/**
 * A response of an action in a scenario.
 */
class ActionResponse extends BasicEntity implements Meta, Validation
{
    use DeepClone;
    use Meta\Implementation;

    /**
     * The title that will be displayed in the process if the action is performed.
     * @var string|DataInstruction
     */
    public $title;
    
    /**
     * Show the response
     * @var string
     * @options never, once, always
     */
    public $display = 'always';

    /**
     * Hard-wired transition for an action response to a specific state. This can be used for actions that always
     * transition to a specific state regardless of the state the process is now in.
     *
     * @var string|null
     */
    public $transition;

    /**
     * Update instruction or array of update instructions.
     * @var UpdateInstruction|UpdateInstruction[]|EntitySet
     */
    public $update;

    /**
     * Cast the entity
     * 
     * @return $this
     */
    public function cast(): self
    {
        if (
            isset($this->update) &&
            !$this->update instanceof UpdateInstruction &&
            !$this->update instanceof EntitySet
        ) {
            // check if update is multidimensional, then make an entity set out of it
            $isMulti = is_array($this->update) && (!in_array(false, array_map('is_object', $this->update)) ||
                    !in_array(false, array_map('is_array', $this->update)));

            if ($isMulti) {
                $this->update = EntitySet::forClass(
                    UpdateInstruction::class,
                    $this->update,
                    null,
                    EntitySet::ALLOW_DUPLICATES
                );
            } else {
                $this->update = UpdateInstruction::fromData($this->update);
            }
        }
        
        return $this->castUsingMeta();
    }

    /**
     * Validates the response
     *
     * @return ValidationResult
     */
    public function validate(): ValidationResult
    {
        $validation = new ValidationResult();

        if (isset($this->update)) {
            $updateValidation = $this->update->validate();
            
            if (is_array($updateValidation)) {
                array_map([$validation, 'add'], $updateValidation, ['update']);
            } else {
                $validation->add($updateValidation, "update");
            }
        }
        
        return $validation;
    }
}
