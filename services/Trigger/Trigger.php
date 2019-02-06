<?php declare(strict_types=1);

namespace Trigger;

use Psr\Container\ContainerInterface;

/**
 * Trigger service interface.
 * The TriggerManager can handle any callable. This is for more for declarations and sequence trigger.
 */
interface Trigger
{
    /**
     * Create a configured clone.
     *
     * @param object|array       $settings
     * @param ContainerInterface $container
     * @return static
     */
    public function withConfig($settings, ContainerInterface $container);

    /**
     * Invoke for the trigger.
     *
     * @param \Process $process
     * @param \Action  $action
     * @return \Response
     */
    public function __invoke(\Process $process, \Action $action): \Response;
}
