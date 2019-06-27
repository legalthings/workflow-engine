<?php declare(strict_types=1);

use GuzzleHttp\Client as HttpClient;
use LTO\Account;
use LTO\Event;
use Psr\Container\ContainerInterface;

return [
    'event.create' => static function() {
        return static function($body, string $latest_hash): Event {
            return new Event($body, $latest_hash);
        };
    },
    'event-chain.http-client' => static function(ContainerInterface $container) {
        $reconfigure = function (HttpClient $client, array $newOptions): HttpClient {
            return new HttpClient($newOptions + $client->getConfig());
        };

        if (!$container->has('config.event_chain.url') || $container->get('config.event_chain.url') === null) {
            return null;
        }

        $options = [
            'base_uri' => $container->get('config.event_chain.url'),
            'signature_key_id' => $container->get(Account::class)->getPublicSignKey(),
        ];

        return $reconfigure($container->get(HttpClient::class), $options);
    },
    EventChainRepository::class => static function(ContainerInterface $container) {
        /** @var HttpClient $client */
        $client = $container->get('event-chain.http-client');
        $createEvent = $container->get('event.create');
        $account = $container->get(Account::class);

        return new EventChainRepository($createEvent, $account, $client);
    },
];
