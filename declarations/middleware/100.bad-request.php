<?php declare(strict_types=1);

return [
    'bad-request' => static function() {
        return new BadRequestMiddleware();
    },
];
