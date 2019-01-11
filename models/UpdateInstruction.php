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
     * @var string
     */
    public $schema;

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
    public $patch = false;
    
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
    public $jmespath;
    
    
    /**
     * Class constructor
     */
    public function __construct()
    {
        $this->cast();
    }
    
    /**
     * Validate the update instruction
     * 
     * @return Jasny\ValidationResult
     */
    public function validate(): ValidationResult
    {
        $validation = new ValidationResult();
        
        if (isset($this->jmespath)) {
            try {
                $parser = new JmesPath\Parser();
                $parser->parse($this->jmespath);
            } catch (JmesPath\SyntaxErrorException $e) {
                $validation->addError("jmespath has a syntax error");
            }
        }
        
        return $validation;
    }
}
