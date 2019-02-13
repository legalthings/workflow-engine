<?php declare(strict_types=1);

namespace JsonView;

/**
 * Output Scenario in a readable way, omitting properties where possible.
 *
 * Examples;
 *   - If all responses of an action have the same value for `display`, set the `display` property for the action.
 *   - If there is only a single 'ok' response, the `responses` property for an action.
 *   - If a state has only one allowed action, use the `action` property rather than `actions`.
 *   - etc
 */
class PrettyScenarioDecorator
{
    /**
     * Apply decorator to data.
     *
     * @param \Scenario $process
     * @param \stdClass $data
     */
    public function __invoke(\Scenario $scenario, \stdClass $data)
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
