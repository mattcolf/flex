<?php

declare(strict_types=1);

namespace MattColf\Flex\Route;

use InvalidArgumentException;
use MattColf\Flex\Utility\TypeValidation;
use Psr\Container\ContainerInterface;

/**
 * The route loader parses the routes configuration and add routes to the router. Routes should be
 * defined in configuration as follows.
 *
 * $routes = [
 *      // single route (GET only)
 *      'route1' => [
 *          'method' => 'GET'
 *          'path' => '/route1',
 *          'stack' => [
 *              'callable1',
 *              'callable2'
 *          ]
 *      ],
 *      // single route (GET and POST)
 *      'route2' => [
 *          'method' => ['GET', 'POST']
 *          'path' => '/route2',
 *          'stack' => [
 *              'callable1',
 *              'callable2'
 *          ]
 *      ],
 *      // group of routes
 *      'group1' => [
 *          'path' => '/group2',
 *          'stack' => [
 *              'callable1',
 *              'callable2'
 *          ],
 *          // routes in groups follow the same layout as normal routes, but all route strings will be
 *          // prefixed by the group route and the group stack will be run before the route stack
 *          'routes' => [
 *              'route3' => [
 *                  'method' => 'GET',
 *                  'path' => '/route3'
 *                  'stack' => [
 *                      'callable3',
 *                      'callable4'
 *                  ]
 *              ],
 *              'route4' => [
 *                  'method' => 'POST',
 *                  'path' => '/route4',
 *                  'stack' => [
 *                      'callable5',
 *                      'callable6'
 *                  ]
 *              ]
 *          ]
 *      ]
 * ]
 *
 * Note that each route entry must have, at minimum, a key that denotes the name and a stack of callable
 * functions (either middleware or controllers) that should be run when that route is matched.
 */
class RouteLoader
{
    // Route Details
    const METHOD = 'method';
    const PATH = 'path';
    const STACK = 'stack';
    const ROUTES = 'routes';

    // Error Messages
    const ERR_ROUTE_NAME = 'Each route must have a string name.';
    const ERR_ROUTE_FORMAT = 'Unable to build route %s. Must be an array.';
    const ERR_ROUTE_MISSING_PATH = 'Unable to build route %s. Missing required option "path".';
    const ERR_ROUTE_MISSING_STACK = 'Unable to build route %s. Missing required option "stack".';
    const ERR_ROUTE_FORMAT_METHOD = 'Unable to build route %s. Option "method" be a string or array of strings.';
    const ERR_ROUTE_FORMAT_PATH = 'Unable to build route %s. Option "path" must be a string.';
    const ERR_ROUTE_FORMAT_STACK = 'Unable to build route %s. Option "stack" must be an array of strings.';
    const ERR_ROUTE_FORMAT_ROUTES = 'Unable to build route %s. Option "routes" must be an array.';
    const ERR_ROUTE_FORMAT_OPTIONS = 'Unable to build route %s. Option "options" must be an array.';
    const ERR_ROUTE_COUNT_STACK = 'Unable to build route %s. Option "stack" must have at least one member.';

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var array
     */
    private $routes;

    /**
     * @param ContainerInterface $container
     * @param RouterInterface $router
     * @param array $routes
     */
    public function __construct(ContainerInterface $container, RouterInterface $router, array $routes = [])
    {
        $this->container = $container;
        $this->router = $router;
        $this->routes = $routes;
    }

    /**
     * Add a new route
     *
     * @param array $route
     */
    public function addRoute(array $route)
    {
        $this->routes[] = $route;
    }

    /**
     * Load routes from configuration
     *
     * @param array $routes
     */
    public function load(array $routes)
    {
        $routes = array_merge($this->routes, $routes);

        foreach ($routes as $name => $details) {
            TypeValidation::validateType($name, 'string', static::ERR_ROUTE_NAME);
            TypeValidation::validateType($details, 'array', static::ERR_ROUTE_FORMAT);

            if (isset($details[static::ROUTES])) {
                $this->loadGroup($name, $details);
            } else {
                $this->loadRoute($name, $details);
            }
        }
    }

    /**
     * Load a route into the router
     *
     * @param string $name
     * @param array $details
     */
    private function loadRoute(string $name, array $details)
    {
        $methods = $details[static::METHOD] ?? 'GET';
        $path = $details[static::PATH] ?? null;
        $stack = $details[static::STACK] ?? null;
        $meta = $this->getMeta($details);

        if (!is_string($path)) {
            throw new InvalidArgumentException(sprintf(static::ERR_ROUTE_MISSING_PATH, $name));
        }

        if (!is_array($stack)) {
            throw new InvalidArgumentException(sprintf(static::ERR_ROUTE_MISSING_STACK, $name));
        }

        TypeValidation::validateType($methods, 'string|string[]', sprintf(static::ERR_ROUTE_FORMAT_METHOD, $name));
        TypeValidation::validateType($path, 'string', sprintf(static::ERR_ROUTE_FORMAT_PATH, $name));
        TypeValidation::validateType($stack, 'array', sprintf(static::ERR_ROUTE_FORMAT_STACK, $name));

        $this->router->addRoute(new Route($name, (array) $methods, $path, $stack, $meta));
    }

    /**
     * Load a group of routes into the router
     *
     * @param string $name
     * @param array $details
     */
    private function loadGroup(string $name, array $details)
    {
        $routes = $details[static::ROUTES] ?? [];
        $path = $details[static::PATH] ?? '';
        $stack = $details[static::STACK] ?? [];

        TypeValidation::validateType($routes, 'array', sprintf(static::ERR_ROUTE_FORMAT_ROUTES, $name));
        TypeValidation::validateType($path, 'string', sprintf(static::ERR_ROUTE_FORMAT_PATH, 'group '.$name));
        TypeValidation::validateType($stack, 'array', sprintf(static::ERR_ROUTE_FORMAT_STACK, 'group'.$name));

        foreach ($routes as $name => $route) {
            // merge group details into route details
            $route = array_merge($details, $route);
            $route[static::PATH] = $path . $route[static::PATH] ?? '';
            $route[static::STACK] = array_merge($stack, $route[static::STACK] ?? []);

            $this->loadRoute($name, $route);
        }
    }

    /**
     * Get the meta details for a route (everything other than defined keys)
     *
     * @param array $details
     * @return array
     */
    private function getMeta(array $details) : array
    {
        return array_diff_key($details, array_flip([static::METHOD, static::PATH, static::STACK, static::ROUTES]));
    }
}