<?php declare(strict_types=1);

return [
    JsonView::class => static function() {
        return new JsonSchemaFactory([
            'process:pretty' => new JsonView\PrettyProcessDecorator(),
            'scenario:pretty' => new JsonView\PrettyScenarioDecorator(),
        ]);
    },
];
