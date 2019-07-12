<?php declare(strict_types=1);

use JsonSchema\Validator\Wrapper as JsonSchemaValidator;
use JsonSchema\Validator\Repository as JsonSchemaRepository;
use JsonSchema\Validator\Loader\FileSource;
use Jasny\Container\AutowireContainerInterface;

return [
    JsonSchemaFactory::class => static function() {
        return new JsonSchemaFactory();
    },
    JsonSchemaValidator::class => static function(AutowireContainerInterface $container) {
        $validator = new JsonSchema\Validator();
        $repository = $container->get(JsonSchemaRepository::class);

        return new JsonSchemaValidator($validator, $repository);
    },
    JsonSchemaRepository::class => static function() {
        $loaders = [
            'file' => new FileSource()
        ];

        return new JsonSchemaRepository($loaders);
    }
];
