<?php declare(strict_types=1);

return [
    JsonView::class => static function() {
        return new JsonView([
            'pretty.process' => new JsonView\PrettyProcessDecorator(),
            'pretty.scenario' => new JsonView\PrettyScenarioDecorator(),
        ]);
    },
];
