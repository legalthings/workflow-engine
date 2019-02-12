<?php declare(strict_types=1);

use Jasny\ApplicationEnv;

/**
 * Default controller
 */
class DefaultController extends BaseController
{
    /**
     * @var stdClass
     */
    protected $app;

    /**
     * @var string  application environment
     */
    protected $env;

    /**
     * @var LTO\Account
     */
    protected $node;


    /**
     * @param stdClass       $appConfig  "config.app"
     * @param ApplicationEnv $env
     * @param LTO\Account    $node
     */
    public function __construct(stdClass $appConfig, ApplicationEnv $env, LTO\Account $node)
    {
        $this->app = $appConfig;
        $this->env = (string)$env;
        $this->node = $node;
    }

    /**
     * Show API info
     */
    public function run(): void
    {
        $info = [
            'name' => $this->app->name ?? '',
            'version' => $this->app->version ?? '',
            'description' => $this->app->description ?? '',
            'env' => $this->env,
            'url' => defined('BASE_URL') ? BASE_URL : null,
            'signkey' => $this->node->getPublicSignKey()
        ];

        $this->output($info, 'json');
    }
}
