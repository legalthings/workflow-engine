<?php declare(strict_types=1);

namespace Trigger;

use function get_class_vars;
use Improved as i;
use Psr\Container\ContainerInterface;
use function Jasny\array_only;
use function Jasny\object_get_properties;

/**
 * Base class for trigger services.
 */
abstract class AbstractTrigger
{
    /**
     * JMESPath service
     * @var callable
     */
    protected $jmespath;

    /**
     * JMESPath projection
     * @var string
     */
    protected $projection;


    /**
     * DataPatcher constructor.
     *
     * @param callable $jmespath  "jmespath"
     */
    public function __construct(callable $jmespath)
    {
        $this->jmespath = $jmespath;
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
        return $this->cloneConfigure($settings, ['projection']);
    }

    /**
     * Create a configured clone for specified properties.
     *
     * @param object|array $settings
     * @param array        $properties
     * @return static
     */
    protected function cloneConfigure($settings, array $properties)
    {
        $set = array_only((array)$settings, $properties);
        $current = array_only(get_object_vars($this), $properties);

        if ($current === $set) {
            return $this; // No changes
        }

        $clone = clone $this;

        foreach ($set as $prop => $value) {
            $clone->$prop = $value; // Sets protected properties
        }

        return $clone;
    }


    /**
     * Project the input based on jmespath.
     *
     * @param object|array $input
     * @return object
     */
    protected function project($input)
    {
        if ($this->projection === null) {
            return $input;
        }

        try {
            $output = i\function_call($this->jmespath, $this->projection, $input);
        } catch (\JmesPath\SyntaxErrorException $e) {
            throw new \RuntimeException("JMESPath projection failed: " . $e->getMessage(), 0, $e);
        }

        return i\type_cast($output, 'object');
    }


    /**
     * Apply trigger to an action.
     *
     * @param \Action $action
     * @return \Response
     */
    abstract protected function apply(\Action $action): \Response;

    /**
     * Invoke for the trigger.
     *
     * @param \Process $process
     * @param \Action  $action
     * @return \Response
     */
    public function __invoke(\Process $process, \Action $action): \Response
    {
        return $this->apply($action);
    }
}
