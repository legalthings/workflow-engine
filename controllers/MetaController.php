<?php declare(strict_types=1);

/**
 * Set scenario and process meta data.
 */
class MetaController extends BaseController
{
    /**
     * @var ProcessGateway
     */
    protected $processes;

    /**
     * @var ScenarioGateway
     */
    protected $scenarios;

    /**
     * @var LTO\Account
     */
    protected $node;

    /**
     * @var bool
     */
    protected $noAuth;

    /**
     * Class constructor for DI.
     *
     * @param ProcessGateway  $processes
     * @param ScenarioGateway $scenarios
     * @param LTO\Account     $node
     * @param bool            $noAuth     "config.noauth"
     */
    public function __construct(
        ProcessGateway $processes,
        ScenarioGateway $scenarios,
        LTO\Account $node,
        bool $noAuth
    ) {
        $this->setServices(func_get_args());
    }


    /**
     * Called before each request.
     */
    public function before(): void
    {
        /** @var LTO\Account|null $account */
        $account = $this->request->getAttribute('account');

        if (!$this->noAuth && $account === null) {
            $this->cancel()->forbidden('HTTP request not signed');
        } elseif ($account !== null && $account->getPublicSignKey() !== $this->node->getPublicSignKey()) {
            $this->cancel()->forbidden('HTTP request was not signed by node');
        }
    }

    /**
     * Update a process meta information.
     *
     * @param string $id  Scenario id
     */
    public function updateProcessMetaAction(string $id): void
    {
        $process = $this->processes->fetch($id);

        $process->meta->setValues($this->getInput());
        $this->processes->save($process, ['only' => 'meta']);

        $this->output($process);
    }

    /**
     * Update a scenario meta information.
     *
     * @param string $id  Scenario id
     */
    public function updateScenarioMetaAction(string $id): void
    {
        $scenario = $this->scenarios->fetch($id);

        $scenario->meta->setValues($this->getInput());
        $this->scenarios->save($scenario, ['only' => 'meta']);

        $this->output($scenario);
    }
}
