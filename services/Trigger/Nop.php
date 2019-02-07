<?php declare(strict_types=1);

namespace Trigger;

use Response;

/**
 * Trigger that performs no operation.
 */
class Nop extends AbstractTrigger
{
    /**
     * Apply trigger to an action.
     *
     * @param \Action $action
     * @return \Response
     */
    protected function apply(\Action $action): \Response
    {
        $projected = $this->project($action);

        return (new Response)->setValues([
            'key' => $projected->trigger_response ?? $projected->default_response ?? 'ok',
            'data' => $projected->data ?? null,
        ]);
    }
}
