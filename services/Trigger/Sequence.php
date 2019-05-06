<?php declare(strict_types=1);

namespace Trigger;

use Improved\IteratorPipeline\Pipeline;
use Psr\Container\ContainerInterface;
use Improved as i;
use Response;
use InvalidArgumentException;

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
     * Trigger constructor
     */
    public function __construct()
    {

    }

    /**
     * Create a configured clone.
     *
     * @param object|array       $settings
     * @param ContainerInterface $container
     * @return static
     */
    public function withConfig($settings, ContainerInterface $container)
    {
        $settings = i\type_cast($settings, 'object');

        if (!isset($settings->triggers)) {
            throw new InvalidArgumentException("Sequence trigger config should have 'triggers' setting");
        }

        $clone = clone $this;

        // Similar logic as in `TriggerManager` declaration.
        $clone->triggers = Pipeline::with($settings->triggers ?? [])
            ->map(static function($entry) use ($container) {
                return $container->get(($entry->type ?? 'unknown') . '_trigger')
                    ->withConfig($entry, $container);
            })
            ->toArray();

        return $clone;
    }

    /**
     * Invoke for the action
     *
     * @param \Process $process
     * @param \Action $action
     * @return \Response
     */
    public function __invoke(\Process $process, \Action $action): \Response
    {
        $response = (new Response)->setValues([
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
