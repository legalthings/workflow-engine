<?php

declare(strict_types=1);

/**
 * Scenario controller.
 *
 * `NotFoundMiddleware` and `ValidationMiddleware` are responsible for giving 40x responses for exceptions thrown by
 * the model related services.
 */
class ScenarioController extends BaseController
{
    /**
     * @var ScenarioGateway
     */
    protected $scenarios;

    /**
     * ScenarioController constructor for DI.
     *
     * @param LTO\Account $account
     * @param ScenarioGateway $scenarios
     * @param IdentityGateway $identities
     * @param JsonView $jsonView
     */
    public function __construct(
        LTO\Account $node,
        ScenarioGateway $scenarios,
        IdentityGateway $identities,
        JsonView $jsonView
    ) {
        object_init($this, get_defined_vars());
    }

    /**
     * Executed before each action.
     * @throws AuthException
     */
    public function before()
    {
        $this->authz(Identity::AUTHZ_USER, "Signing identity isn't allowed to manage scenarios");
    }

    /**
     * Add a scenario
     *
     * @throws \Jasny\ValidationException
     */
    public function addAction()
    {
        $scenario = $this->scenarios->create()->setValues($this->getInput());
        $scenario->validate()->mustSucceed();

        $exists = isset($scenario->id) && $this->scenarios->exists($scenario->id);
        if ($exists) {
            $this->noContent();
            return;
        }

        $this->scenarios->save($scenario);
        $this->output($scenario);
    }

    /**
     * Get a scenario.
     *
     * @param string $id  Scenario id
     * @throws EntityNotFoundException
     */
    public function getAction(string $id)
    {
        $scenario = $this->scenarios->fetch($id);

        $this->output($scenario);
    }
}
