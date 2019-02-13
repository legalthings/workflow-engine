<?php declare(strict_types=1);

namespace JsonView;

/**
 * Output Process in a readable way, omitting properties where possible.
 */
class PrettyProcessDecorator
{
    /**
     * Apply decorator to data.
     *
     * @param \Process $process
     * @param \stdClass $data
     */
    public function __invoke(\Process $process, \stdClass $data)
    {
        // TODO
    }

    /**
     * Get the options for json_encode.
     *
     * @return int
     */
    public function getJsonOptions(): int
    {
        return \JSON_PRETTY_PRINT;
    }
}
