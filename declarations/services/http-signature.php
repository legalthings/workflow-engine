<?php declare(strict_types=1);

/*
 * Service and client middleware to sign requests. Verifying requests is done via server middleware (see
 * `declarations/middleware/010.http-signature.php`).
 *
 * Note that requests are not signed by default but rely on the `signature_key_id` Guzzle option, which must be set
 * to the public key of the LTO account as registered in this container.
 */

use Psr\Container\ContainerInterface;
use Jasny\HttpSignature\HttpSignature;
use Jasny\HttpSignature\ClientMiddleware;
use LTO\Account;
use LTO\AccountFactory;
use LTO\Account\SignCallback;
use LTO\Account\VerifyCallback;

return [
    HttpSignature::class => function (ContainerInterface $container) {
        $node = $container->get(Account::class);
        $factory = $container->get(AccountFactory::class);

        $service = new HttpSignature(
            ['ed25519', 'ed25519-sha256'],
            new SignCallback($node),
            function() { return true; }
            // TODO; temporary disable verification, until whole httpSignature is fixed
            // new VerifyCallback($factory)
        );

        $requiredReadHeaders = ['(request-target)', 'date', 'x-identity', 'x-original-key-id'];
        $requiredWriteHeaders = array_merge($requiredReadHeaders, ['content-type', 'digest']);

        return $service
            ->withRequiredHeaders('default', $requiredReadHeaders)
            ->withRequiredHeaders('POST', $requiredWriteHeaders)
            ->withRequiredHeaders('PUT', $requiredWriteHeaders);
    },
    ClientMiddleware::class => function(ContainerInterface $container) {
        $node = $container->get(Account::class);
        $service = $container->get(HttpSignature::class);

        return new ClientMiddleware($service->withAlgorithm('ed25519'), $node->getPublicSignKey());
    }
];
