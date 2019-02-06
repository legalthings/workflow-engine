<?php declare(strict_types=1);

namespace Trigger;

use Improved\IteratorPipeline\Pipeline;
use Psr\Container\ContainerInterface;
use Response;

/**
 * Perform a number of triggers in sequence.
 */
class Sequence implements Trigger
{
    /**
     * @var callable[]
     */
    protected $triggers = [];

    /**
     * Create a configured clone.
     *
     * @param object|array       $settings
     * @param ContainerInterface $container
     * @return static
     */
    public function withConfig($settings, ContainerInterface $container)
    {
        $settings = type_cast($settings, 'object');

        if (!isset($settings->triggers)) {
            \trigger_error("Sequence trigger config should have 'triggers' setting", \E_USER_WARNING);
        }

        $clone = clone $this;

        // Similar logic as in `TriggerManager` declaration.
        $clone->triggers = Pipeline::with($settings->triggers ?? [])
            ->map(static function($entry) use ($container) {
                $container->get(($entry->type ?? 'unknown') . '_trigger')->withConfig($entry);
            })
            ->toArray();

        return $clone;
    }

    /**
     * Invoke for the trigger.
     *
     * @param \Process $process
     * @param \Action $action
     * @return \Response
     */
    public function __invoke(\Process $process, \Action $action): \Response
    {
        $response = (new \Response)->setValue([
            'action' => $action,
            'key' => $action->default_response,
        ]);

        return Pipeline::with($this->triggers)
            ->reduce(static function(Response $response, callable $trigger) use ($process): Response {
                // Create a cross reference.
                $action = $response->action;
                $action->previous_response = clone $response;

                return $trigger($process, $action);
            }, $response);
    }
}
