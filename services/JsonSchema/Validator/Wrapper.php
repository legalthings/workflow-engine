<?php declare(strict_types=1);

namespace JsonSchema\Validator;

use Scenario;
use JsonSchema\Validator;
use JsonSchema\Constraints\Constraint;
use JsonSchema\Exception\ValidationException;
use Jasny\ValidationResult;

/**
 * Validate json schemas of scenario
 */
class Wrapper
{
    /**
     * @var Validator
     */
    protected $validator;

    /**
     * @var JsonSchemaRepository
     **/
    protected $repository;

    /**
     * Constructor
     *
     * @param IdentityGateway $gateway
     * @param JsonSchemaRepository $repository 
     */
    public function __construct(Validator $validator, Repository $repository)
    {
        $this->validator = $validator;
        $this->repository = $repository;
    }

    /**
     * Invoke this event handler.
     *
     * @param Scenario $scenario
     */
    public function __invoke(Scenario $scenario, ValidationResult $validation): ValidationResult
    {
        $data = json_decode(json_encode($scenario));
        $schema = $this->repository->get($scenario->schema);

        if ($schema === null) {
            return $validation;
        }

        $this->validator->reset();

        try {
            $this->validator->validate($data, $schema, Constraint::CHECK_MODE_EXCEPTIONS);            
        } catch (ValidationException $e) {
            $validation->addError($e->getMessage());
        }

        return $validation;
    }
}
