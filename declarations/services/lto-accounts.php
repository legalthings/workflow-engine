<?php
declare(strict_types=1);

use Psr\Container\ContainerInterface;
use LTO\Account;
use LTO\AccountFactory;

return [
    AccountFactory::class => static function(ContainerInterface $container) {
        $config = $container->get('config.lto');

        return new AccountFactory($config->network ?? 'T');
    },
    Account::class => static function(ContainerInterface $container) {
        $factory = $container->get(AccountFactory::class);
        $accountConfig = $container->get('config.lto.account');

        return $factory->create(arrayify($accountConfig));
    }
];
