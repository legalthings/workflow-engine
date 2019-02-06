<?php declare(strict_types=1);

use LegalThings\DataEnricher;

return [
    DataEnricher::class => static function() {
        return new DataEnricher();
    },
];
