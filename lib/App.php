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
    use ServiceLocator;

    /**
     * This is a static class, it should not be instantiated.
     */
    private function __construct()
    {
    }

    /**
     * Run the application
     */
    public static function run(): void
    {
        $container = self::init();

        self::handleRequest($container);
    }

    /**
     * Initialize the application
     *
     * @return ContainerInterface
     */
    public static function init(): ContainerInterface
    {
        $container = new Container(self::getContainerEntries());
        self::setContainer($container);

        self::initGlobal($container);

        return $container;
    }

    /**
     * @return EntryLoader&iterable<Closure>
     */
    public static function getContainerEntries(): EntryLoader
    {
        $files = new ArrayIterator(glob('declarations/services/*.php', GLOB_BRACE));

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
     * Use the router to handle the current HTTP request.
     *
     * @param ContainerInterface $container
     */
    protected static function handleRequest(ContainerInterface $container): void
    {
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
