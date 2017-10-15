<?php

declare(strict_types=1);

namespace MattColf\Flex\Utility;

use InvalidArgumentException;
use Psr\Container\ContainerInterface;

/**
 * Handle the resolution of callable references
 */
class CallableResolver
{
    const ERR_REFERENCE_TYPE = 'Unable to resolve callable. Reference has wrong type. %s';
    const ERR_REFERENCE_ARRAY_FORMAT = 'Unable to resolve callable. Array must have format ';
    const ERR_REFERENCE_CONTAINER = 'Unable to resolve callable from container. Key %s does not exist.';
    const ERR_REFERENCE_CONTAINER_TYPE = 'Unable to run reference after resolution from container. %s';

    /**
     * Resolve a callable reference to a valid callable
     *
     * Supports the following reference types:
     *      - 'class_name::method' (static method)
     *      - 'class_name::method' (non-static method on class with no constructor arguments)
     *      - 'class_name' (class with static __invoke method)
     *      - 'class_name' (class with non-static __invoke method and no constructor arguments)
     *      - ['class_name', 'method'] (class with static method)
     *      - ['class_name', 'method'] (class with non-static method on class with no constructor arguments)
     *      - [object, 'method'] (object with arbitrary method)
     *      - 'container_key' (container key resolves to class with __invoke method)
     *      - 'container_key' (container key resolves to closure)
     *      - 'container_key::method' (container key resolves to class with arbitrary method)
     *      - object (object with __invoke method)
     *      - closure
     *
     * @param ContainerInterface $container
     * @param string|array|callable $reference
     * @return callable
     * @throws InvalidArgumentException
     */
    public static function resolve(ContainerInterface $container, $reference)
    {
        // function, object
        if (is_callable($reference)) {
            return $reference;
        }

        if (is_array($reference)) {

            // ['class name', 'method']
            if (is_array($reference) && isset($reference[0]) && is_string($reference[0]) && !$container->has($reference[0])) {
                $reference[0] = static::objectFactory($reference[0]);
            }

            // [object, 'method'], ['class_name', 'static method']
            if (is_callable($reference)) {
                return $reference;
            }

            return static::containerProxy($container, $reference[0], $container[1]);
        }

        if (!is_string($reference)) {
            throw new InvalidArgumentException(sprintf(static::ERR_REFERENCE_TYPE, var_export($reference, true)));
        }

        // 'container_key::method', 'class::method'
        if (is_string($reference) && strpos($reference, '::') !== false) {
            return static::resolve($container, explode('::', $reference));
        }

        // 'class name'
        if (is_string($reference) && !$container->has($reference)) {
            $reference = static::objectFactory($reference);
        }

        // function, object
        if (is_callable($reference)) {
            return $reference;
        }

        // 'container_key'
        return static::containerProxy($container, $reference);
    }

    /**
     * Resolve an object by class name, if it exists
     *
     * @param string $class
     * @return object|string
     */
    private static function objectFactory(string $class)
    {
        if (class_exists($class)) {
            return new $class;
        }

        return $class;
    }

    /**
     * Return a proxy closure for a container reference
     *
     * @param ContainerInterface $container
     * @param string $key
     * @param string|null $method
     * @return callable
     * @throws InvalidArgumentException
     */
    private static function containerProxy(ContainerInterface $container, string $key, string $method = null) : callable
    {
        if (!$container->has($key)) {
            throw new InvalidArgumentException(sprintf(static::ERR_REFERENCE_CONTAINER, $key));
        }

        // lazy load from container
        return function () use ($container, $key, $method) {
            $reference = $container->get($key);

            if (!is_callable($reference)) {
                throw new InvalidArgumentException(static::ERR_REFERENCE_CONTAINER_TYPE, var_export($reference, true));
            }

            if ($method !== null) {
                $reference = [$reference, $method];
            }

            return call_user_func_array($reference, func_get_args());
        };
    }
}