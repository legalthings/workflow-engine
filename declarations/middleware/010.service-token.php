<?php declare(strict_types=1);

use Jasny\DB;

return [
    'service-token' => function(RouterInterface $router, ContainerInterface $cont) {
        $token =
            ($cont->has('config.flow.service_token') ? $cont->get('config.flow.service_token') : null) ??
            ($cont->has('config.legalflow.service_token') ? $cont->get('config.legalflow.service_token') : null) ??
            ($cont->has('config.service_token') ? $cont->get('config.service_token') : null);

        return new ServiceTokenMiddleware($token);
    }
];

