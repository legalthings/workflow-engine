<?php

use Jasny\DB\Entity\Meta;
use Jasny\DB\Entity\Validation;
use Jasny\ValidationResult;

/**
 * The update instruction for a response
 */
class UpdateInstruction extends BasicEntity implements Meta, Validation
{
    use DeepClone;
    use Meta\Implementation;

    /**
     * A reference to the data in the process that should be updated.
     * Uses dot notation.
     * 
     * @var mixed
     */
    public $select;
    
    /**
     * Whether patch or replace existing objects or array.
     * @var boolean
     */
    public $patch = true;
    
    /**
     * Use explicit data instead of response data
     * @var mixed
     */
    public $data;
    
    /**
     * JMESPath query.
     * Transform the response using JMESPath before updating the process.
     * @see http://jmespath.org/
     * 
     * @var string
     */
    public $projection;
    
    /**
     * @inheritDoc
     */
    public function cast()
    {
        if (is_associative_array($this->data)) {
            $this->data = objectify($this->data);
        }

        return parent::cast();
    }

    /**
     * Validate the update instruction
     * 
     * @return Jasny\ValidationResult
     */
    public function validate(): ValidationResult
    {
        $validation = new ValidationResult();

        if (isset($this->projection)) {
            try {
                $parser = new JmesPath\Parser();
                $parser->parse($this->projection);
            } catch (JmesPath\SyntaxErrorException $e) {
                $validation->addError("jmespath projection has a syntax error: " . $e->getMessage());
            }
        }
        
        return $validation;
    }
}
