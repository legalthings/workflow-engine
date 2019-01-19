<?php

use Jasny\DB\Entity\Validation;
use Jasny\DB\Entity\Meta;
use Jasny\DB\EntitySet;
use Jasny\ValidationResult;

/**
 * A response of an action as defined in the scenario.
 */
class AvailableResponse extends BasicEntity implements Meta, Validation
{
    use DeepClone;
    use Meta\Implementation {
        cast as private metaCast;
    }

    /**
     * @var string
     */
    public $key;
    
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
        
        return $this->metaCast();
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
