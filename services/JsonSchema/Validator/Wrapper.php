<?php declare(strict_types=1);

namespace JsonSchema\Validator;

use Scenario;
use JsonSchema\Validator;
use JsonSchema\Constraints\Constraint;

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
    public function __invoke(Scenario $scenario): void
    {
        $data = json_decode(json_encode($scenario));
        $schema = $this->repository->get($scenario->schema);

        if ($schema !== null) {
            $this->validator->reset();
            $this->validator->validate($data, $schema, Constraint::CHECK_MODE_EXCEPTIONS);            
        }
    }
}
