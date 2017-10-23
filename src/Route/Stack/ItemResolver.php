<?php

declare(strict_types=1);

namespace MattColf\Flex\Route\Stack;

use InvalidArgumentException;
use Interop\Http\Server\MiddlewareInterface;
use Interop\Http\Server\RequestHandlerInterface;
use Psr\Container\ContainerInterface;

/**
 * Resolves stack item references into stack item objects (middleware or controller)
 */
class ItemResolver
{
    const ERR_REFERENCE_TYPE = 'Unable to resolve reference. Unsupported type. %s';
    const ERR_REFERENCE_FACTORY = 'Unable to resolve string reference. Class does not exist. %s';
    const ERR_REFERENCE_CONTAINER = 'Unable to resolve container reference. Key %s does not exist.';

    /**
     * Resolve a middleware reference
     *
     * @param ContainerInterface $container
     * @param mixed $reference
     * @return MiddlewareInterface
     */
    public static function resolveMiddleware(ContainerInterface $container, $reference) : MiddlewareInterface
    {
        if ($reference instanceof MiddlewareInterface) {
            return $reference;
        }

        if (is_callable($reference)) {
            return new DeferredMiddleware($reference);
        }

        return static::resolveMiddleware($container, static::resolve($container, $reference));
    }

    /**
     * Resolve a controller reference
     *
     * @param ContainerInterface $container
     * @param mixed $reference
     * @return RequestHandlerInterface
     */
    public static function resolveController(ContainerInterface $container, $reference) : RequestHandlerInterface
    {
        if ($reference instanceof RequestHandlerInterface) {
            return $reference;
        }

        if (is_callable($reference)) {
            return new DeferredController($reference);
        }

        return static::resolveController($container, static::resolve($container, $reference));
    }

    /**
     * Resolve a stack item reference
     *
     * @param ContainerInterface $container
     * @param mixed $reference
     * @return callable
     * @throws InvalidArgumentException
     */
    private static function resolve(ContainerInterface $container, $reference) : callable
    {
        if ($reference instanceof MiddlewareInterface) {
            return [$reference, 'process'];
        }

        if ($reference instanceof RequestHandlerInterface) {
            return [$reference, 'handle'];
        }

        if (is_callable($reference)) {
            return $reference;
        }

        if (is_string($reference)) {

            // container_key::method, class::method
            if (strpos($reference, '::') !== false) {
                return static::resolve($container, explode('::', $reference));
            }

            // container_key
            if ($container->has($reference)) {
                return static::resolve($container, static::container($container, $reference));
            }

            // class_name
            return static::resolve($container, static::factory($reference));
        }

        if (is_array($reference) && count($reference) === 2 && is_string($reference[0]) && is_string($reference[1])) {

            // [container_key, method]
            if ($container->has($reference[0])) {
                return static::resolve($container, static::container($container, $reference[0], $reference[1]));
            }

            // [class_name, method], [class_name, static_method]
            return static::resolve($container, [static::factory($reference[0]), $reference[1]]);
        }

        throw new InvalidArgumentException(sprintf(static::ERR_REFERENCE_TYPE, var_export($reference, true)));
    }

    /**
     * Proxy a container reference. Reference must resolve to a callable or an object.
     *
     * @param ContainerInterface $container
     * @param string $key
     * @param string|null $method
     * @return callable
     */
    private static function container(ContainerInterface $container, string $key, string $method = null) : callable
    {
        if (!$container->has($key)) {
            throw new InvalidArgumentException(sprintf(static::ERR_REFERENCE_CONTAINER, $key));
        }

        // lazy load from container
        return function () use ($container, $key, $method) {

            if ($method === null) {
                $reference = static::resolve($container, $container->get($key));
            } else {
                $reference = static::resolve($container, [$container->get($key), $method]);
            }

            return call_user_func_array($reference, func_get_args());
        };
    }

    /**
     * Create an object by class name
     *
     * @param string $class
     * @return object
     * @throws InvalidArgumentException
     */
    private static function factory(string $class) : object
    {
        if (class_exists($class)) {
            return new $class;
        }

        throw new InvalidArgumentException(sprintf(static::ERR_REFERENCE_FACTORY, var_export($class, true)));
    }
}