<?php declare(strict_types=1);

use Psr\Container\ContainerInterface;

/**
 * Trait to use App class as service locator.
 * @deprecated
 *
 * Don't use any of these methods. This is for BC and old services that are not yet rewritten to DI.
 */
trait ServiceLocator
{
    /**
     * @var ContainerInterface
     */
    protected static $container;

    /**
     * Get the app configuration.
     * @deprecated
     *
     * @return stdClass
     */
    public static function config(): \stdClass
    {
        return self::getContainer()->get('config');
    }

    /**
     * Get application environment.
     * @deprecated
     *
     * @param string  $check         Only return if env matches
     * @return string|false
     */
    public static function env($check = null)
    {
        $env = self::getContainer()->get(ApplicationEnv::class);

        return $check === null || $env->is($check) ? (string)$env : false;
    }

    /**
     * Get application reference
     * This is the application name without the vendor namespace
     * @deprecated
     *
     * @return string  Example foo instead of legalthings/foo
     */
    public static function reference()
    {
        $name = self::getContainer()->get('config')->app->name ?? null;

        return str_replace('legalthings/', '', $name);
    }

    /**
     * Get the app container.
     * @deprecated
     *
     * @return ContainerInterface
     * @throws LogicException if the container is not set yet
     */
    public static function getContainer(): ContainerInterface
    {
        if (self::$container === null) {
            throw new LogicException("This container is not set");
        }

        trigger_error("App class shouldn't be used as service locator", E_USER_DEPRECATED);

        return self::$container;
    }

    /**
     * Set the container.
     * @deprecated
     *
     * @param ContainerInterface $container
     */
    public static function setContainer(ContainerInterface $container): void
    {
        self::$container = $container;
    }
}
