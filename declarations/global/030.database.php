<?php declare(strict_types=1);

/**
 * @internal The new Jasny DB layer will not use the global scope.
 */

use Psr\Container\ContainerInterface;
use Jasny\DB;

return static function(ContainerInterface $container) {
    DB::configure($container->get('config.db'));

    if (DB::getSettings('flow-mongo') !== null) {
        DB::conn('flow-mongo')->useAs('default');
    }
};
