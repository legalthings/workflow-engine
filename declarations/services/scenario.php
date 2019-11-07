<?php declare(strict_types=1);

use JsonSchema\Validator\Wrapper as JsonSchemaValidator;
use Jasny\Container\AutowireContainerInterface;
use Jasny\EventDispatcher\EventDispatcher;

return [
    "scenario_events" => static function(AutowireContainerInterface $container) {
        $validateJsonSchema = $container->get(JsonSchemaValidator::class);

        return (new EventDispatcher)
            ->on('validate', $validateJsonSchema);
    },
    ScenarioGateway::class => static function(AutowireContainerInterface $container) {
        return $container->autowire(ScenarioGateway::class);
    }
];
