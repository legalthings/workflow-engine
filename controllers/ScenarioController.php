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
     * @param ScenarioGateway $scenarios
     */
    public function __construct(ScenarioGateway $scenarios, JsonView $jsonView)
    {
        $this->scenarios = $scenarios;
        $this->jsonView = $jsonView;
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
