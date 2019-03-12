<?php

use Jasny\HttpMessage\ServerRequest;
use Jasny\Container\Container;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Psr\Container\ContainerInterface;
use Monolog\Logger;
use Monolog\Handler\TestHandler;

$httpTriggerHistory = [];

// Overwrite the following container entries
$overwrite = [
    ServerRequestInterface::class => static function() {
        return new ServerRequest();
    },
    LoggerInterface::class => static function() {
        return new Logger('', [new TestHandler()]);
    },

    'http.history' => static function() use (&$httpTriggerHistory) {
        return $httpTriggerHistory;
    },
    GuzzleHttp\Handler\MockHandler::class => static function() {
        return new GuzzleHttp\Handler\MockHandler();
    },
    GuzzleHttp\ClientInterface::class => static function(ContainerInterface $container) use (&$httpTriggerHistory) {
        $mock = $container->get(GuzzleHttp\Handler\MockHandler::class);
        
        $handler = GuzzleHttp\HandlerStack::create($mock);
        $handler->push(GuzzleHttp\Middleware::history($httpTriggerHistory));
        
        return new GuzzleHttp\Client(['handler' => $handler]);
    },
    GuzzleHttp\Client::class => static function(ContainerInterface $container) {
        return $container->get(GuzzleHttp\ClientInterface::class); // Alias
    }
];

$entries = new AppendIterator();
$entries->append(App::getContainerEntries());
$entries->append(new ArrayIterator($overwrite));

$container = new Container($entries);

// Setup global state
App::setContainer($container);

Jasny\DB::resetGlobalState();
Jasny\DB::configure($container->get('config.db'));

return $container;
