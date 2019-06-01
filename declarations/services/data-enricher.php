<?php declare(strict_types=1);

use LegalThings\DataEnricher;
use DataEnricher\ResourceId;

return [
    DataEnricher::class => static function() {
        $dataEnricher = new DataEnricher();
        $dataEnricher->processors[] = new ResourceId('<id>');

        return $dataEnricher;
    },
];
