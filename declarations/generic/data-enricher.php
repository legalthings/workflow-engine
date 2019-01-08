<?php declare(strict_types=1);

use LegalThings\DataEnricher;

return [
    DataEnricher::class => function() {
        return new DataEnricher();
    },
];
