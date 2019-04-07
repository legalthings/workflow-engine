<?php

use Jasny\Config;
use Jasny\DotKey;
use Aws\DynamoDb\DynamoDbClient;

/**
 * Application config
 *
 * Get's app's name, version and description from composer.json.
 * 
 * Uses the application environment to load environment specific configuration files.
 * If the configuration exists in DynamoDB, it will override the configuration from file.
 * Eg when `APPLICATION_ENV='dev.foo.bar'`, loads
 *  - settings.yml
 *  - settings.dev.yml
 *  - settings.dev.foo.yml
 *  - settings.dev.foo.bar.yml
 *  - settings.local.yml
 *  - settings.dev.local.yml
 *  - settings.dev.foo.local.yml
 *  - settings.dev.foo.bar.local.yml
 *
 * @codeCoverageIgnore
 */
class AppConfig extends Config
{
    /**
     * Load the application settings.
     * 
     * @param string $env      Application environment
     * @param array  $options
     */
    public function load($env, array $options = []): Config
    {
        $this->loadFromComposerJson();
        $this->loadSettings($env);
        $this->loadSettings($env, '.local');
        $this->loadSettings($env, '', 'config/local');

        $this->addEnvironmentVariables();
        $this->addAppVersion();

        $this->loadDynamoConfig();

        $this->addDBPrefix();

        return $this;
    }

    /**
     * Load app settings from composer.json
     */
    protected function loadFromComposerJson()
    {
        if (!file_exists('composer.json')) {
            return;
        }
        
        $this->app = (object)[];
        $app = (new Config)->load('composer.json');

        foreach (['name', 'version', 'description'] as $prop) {
            if (isset($app->$prop)) {
                $this->app->$prop = $app->$prop;
            }
        }
    }
    
    /**
     * Load configuration settings.
     * 
     * @param string $env
     * @param string $suffix
     * @param string $path
     */
    protected function loadSettings($env, $suffix = '', $path = 'config'): void
    {
        if (file_exists("$path/settings{$suffix}.yml")) {
            parent::load("$path/settings{$suffix}.yml");
        }
        
        $parts = explode('.', $env);
        
        for ($i = 1, $m = count($parts); $i <= $m; $i++) {
            $file = "config/settings." . join('.', array_slice($parts, 0, $i)) . "{$suffix}.yml";
            
            if (file_exists($file)) {
                parent::load($file);
            }
        }
    }

    /**
     * Load the configuration from DynamoDB.
     */
    protected function loadDynamoConfig()
    {
        if (!isset($this->dynamoConfig)) {
            return;
        }

        $client = [
            'region' => 'eu-west-1',
            'version' => 'latest'
        ];
        if (isset($this->dynamoConfig->client)) {
            $client = (array)$this->dynamoConfig->client;
        }

        $dynamodb = new DynamoDbClient($client);

        $options = ['table' => 'lt-config', 'optional' => true];
        if (isset($this->dynamoConfig->options)) {
            $options = (array)$this->dynamoConfig->options;
        }

        $adapter = new File(sys_get_temp_dir() . '/flow-config.json');
        $cache = new Cache($adapter);

        $parts = explode('.', self::env());
        for ($i = 1, $m = count($parts); $i <= $m; $i++) {
            $key = join('.', array_slice($parts, 0, $i));
            $options['key'] = $key;
            $config = $cache->get($key);

            if (!$config) {
                $config = new Config();
                $config->load($dynamodb, $options);
                if ($config !== null) {
                    $cache->set($key, $config, 60);
                }
            }

            $this->merge($config);
        }
    }


    /**
     * Add settings from environment variables
     */
    protected function addEnvironmentVariables()
    {
        if (!isset($this->environment_variables)) return;
        
        foreach ($this->environment_variables as $var => $key) {
            if (getenv($var) !== false) {
                DotKey::on($this)->put($key, getenv($var));
            }
        }
    }
    
    /**
     * Add app version based on environment (git commit or ctime)
     * @codeCoverageIgnore
     */
    protected function addAppVersion()
    {
        if (!isset($this->app)) {
            $this->app = (object)[];
        }
        
        if (!isset($this->app->version)) {
            $this->app->version = is_dir('.git') ? trim(`git rev-parse HEAD`) : date('YmdHis', filectime(getcwd()));
        }
    }
    
    /**
     * Add prefix in database name
     */
    protected function addDBPrefix()
    {
        if (isset($this->db->default->prefix)) {
            $this->db->default->database = $this->db->default->prefix . $this->db->default->database;
            unset($this->db->default->prefix);
        }
    }
}
