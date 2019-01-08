<?php declare(strict_types=1);

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Jasny\ApplicationEnv;
use Jasny\Container\Container;
use Jasny\Container\Loader\EntryLoader;
use Jasny\RouterInterface;
use Jasny\HttpMessage\Emitter;

/**
 * Application
 * @codeCoverageIgnore
 */
class App
{
    /**
     * @var ContainerInterface
     */
    protected static $container;


    /**
     * This is a static class, it should not be instantiated.
     */
    private function __construct()
    {
    }

    /**
     * Get the app configuration.
     * @deprecated
     *
     * @return stdClass
     */
    public static function config(): \stdClass
    {
        return self::getContainer()->get('config');
    }

    /**
     * Get application environment.
     * @deprecated
     *
     * @param string  $check         Only return if env matches
     * @return string|false
     */
    public static function env($check = null)
    {
        $env = self::getContainer()->get(ApplicationEnv::class);

        return $check === null || $env->is($check) ? (string)$env : false;
    }

    /**
     * Get application reference
     * This is the application name without the vendor namespace
     * @deprecated
     *
     * @return string  Example foo instead of legalthings/foo
     */
    public static function reference()
    {
        $name = self::getContainer()->get('config')->app->name ?? null;

        return str_replace('legalthings/', '', $name);
    }

    /**
     * Get the app container.
     * @deprecated
     *
     * @return ContainerInterface
     * @throws LogicException if the container is not set yet
     */
    public static function getContainer(): ContainerInterface
    {
        if (self::$container === null) {
            throw new LogicException("This container is not set");
        }

        return self::$container;
    }

    /**
     * Set the container.
     * @deprecated
     *
     * @param ContainerInterface $container
     */
    public static function setContainer(ContainerInterface $container): void
    {
        self::$container = $container;
    }

    /**
     * Initialize the application
     */
    public static function init(): void
    {
        $container = new Container(self::getContainerEntries());
        self::setContainer($container);

        self::initGlobal($container);
    }

    /**
     * @return EntryLoader&iterable<Closure>
     */
    protected static function getContainerEntries(): EntryLoader
    {
        $files = new ArrayIterator(glob('declarations/{generic,models}/*.php', GLOB_BRACE));

        /** @var EntryLoader&iterable<Closure> $entryLoader */
        $entryLoader = new EntryLoader($files);

        return $entryLoader;
    }

    /**
     * Init global environment
     */
    protected static function initGlobal(ContainerInterface $container): void
    {
        $scripts = glob('declarations/global/*.php');

        foreach ($scripts as $script) {
            /** @noinspection PhpIncludeInspection */
            $callback = require $script;

            $callback($container);
        }
    }

    /**
     * Run the application
     */
    public static function run(): void
    {
        self::init();
        self::handleRequest();
    }

    /**
     * Use the router to handle the current HTTP request.
     */
    protected static function handleRequest(): void
    {
        $container = self::getContainer();

        /* @var RouterInterface $router */
        $router = $container->get(RouterInterface::class);

        $request = $container->get(ServerRequestInterface::class);
        $baseResponse = $container->get(ResponseInterface::class);

        $response = $router->handle($request, $baseResponse);

        /** @var Emitter $emitter */
        $emitter = $container->get(Emitter::class);
        $emitter->emit($response);
    }


    /**
     * Send a message to the configured logger.
     *
     * @param string|mixed $message
     */
    public static function debug($message): void
    {
        $container = self::getContainer();

        if (!($container->get('config')->debug ?? false)) {
            return;
        }

        if (!is_scalar($message)) {
            $message = json_encode($message, JSON_PRETTY_PRINT);
        }

        if ($container->get(ApplicationEnv::class)->is('tests')) {
            /** @noinspection ForgottenDebugOutputInspection */
            Codeception\Util\Debug::debug($message);
            return;
        }

        $container->get('logger')->debug($message);
    }
}
