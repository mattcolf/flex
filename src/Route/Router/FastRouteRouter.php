<?php

declare(strict_types=1);

namespace MattColf\Flex\Route\Router;

use FastRoute;
use InvalidArgumentException;
use MattColf\Flex\Route\Route;
use MattColf\Flex\Route\RouterInterface;
use MattColf\Flex\Route\RouterResult;
use MattColf\Flex\Utility\ConfigTrait;
use MattColf\Flex\Utility\HttpUtility;
use Psr\Http\Message\ServerRequestInterface;

class FastRouteRouter implements RouterInterface
{
    use ConfigTrait;

    // Config Keys
    const CACHE_FILE = 'cache_file';

    // Config Defaults
    const DEFAULT_CACHE_FILE = null;

    // Error Messages
    const ERR_NO_ROUTE = 'Route %s does not exist.';

    /**
     * @var FastRoute\RouteCollector
     */
    private $collection;

    /**
     * @var FastRoute\Dispatcher|null
     */
    private $dispatcher = null;

    /**
     * @var Route[]
     */
    private $routes = [];

    /**
     * @param FastRoute\RouteCollector $collection
     * @param array $config
     */
    public function __construct(FastRoute\RouteCollector $collection, array $config = [])
    {
        $this->setConfig($config, [
            static::CACHE_FILE => static::DEFAULT_CACHE_FILE
        ]);

        $this->collection = $collection;
    }

    /**
     * Get the relative URL for a given route name
     *
     * Example:
     *  - /foo/bar
     *
     * @param string $route
     * @param array $params
     * @param array $query
     * @return string
     * @throws InvalidArgumentException
     */
    public function relativeUrlFor(string $route, array $params = [], array $query = []) : string
    {
        $route = $this->getRoute($route);

        if ($route === null) {
            throw new InvalidArgumentException(sprintf(static::ERR_NO_ROUTE, $route));
        }

        $path = $route->getPath();

        foreach ($params as $name => $value) {
            $path = preg_replace(sprintf('#{%s(:[^}]+){0,1}}#', $name), $value, $path);
        }

        if (count($query) > 0) {
            $path = sprintf('%s?%s', $path, HttpUtility::buildQuery($query));
        }

        return $path;
    }

    /**
     * Get the absolute URL for a given route name
     *
     * Example:
     *  - http://foo.com/foo/bar
     *
     * @param ServerRequestInterface $request
     * @param string $route
     * @param array $params
     * @param array $query
     * @return string
     * @throws InvalidArgumentException
     */
    public function absoluteUrlFor(ServerRequestInterface $request, string $route, array $params = [], array $query = []) : string
    {
        // fake a full url to ensure successful parsing
        $parts = parse_url(sprintf('http://test.com%s', $this->relativeUrlFor($route, $params)));

        $uri = $request->getUri();
        $uri = $uri->withPath($parts['path']);
        $uri = $uri->withQuery(HttpUtility::buildQuery(array_merge(HttpUtility::parseQuery($parts[PHP_URL_QUERY] ?? ''), $query)));
        $uri = $uri->withFragment('');

        return (string)$uri;
    }

    /**
     * Add a single route to the router
     *
     * @param Route $route
     * @throws InvalidArgumentException
     */
    public function addRoute(Route $route) : void
    {
        $this->routes[$route->getName()] = $route;

        // reset the dispatcher when new routes are added
        $this->dispatcher = null;

        $this->collection->addRoute($route->getMethods(), $route->getPath(), function () use ($route) {
            return $route;
        });
    }

    /**
     * Get a route by name
     *
     * @param string $name
     * @return Route|null
     */
    public function getRoute(string $name) : ?Route
    {
        return $this->routes[$name] ?? null;
    }

    /**
     * Resolve the current route
     *
     * @param ServerRequestInterface $request
     * @return RouterResult
     */
    public function resolve(ServerRequestInterface $request) : RouterResult
    {
        $details = $this->getDispatcher()->dispatch($request->getMethod(), $request->getUri()->getPath());

        if ($details[0] === FastRoute\Dispatcher::NOT_FOUND) {
            return new RouterResult(RouterResult::STATUS_NOT_FOUND);
        }

        if ($details[0] === FastRoute\Dispatcher::METHOD_NOT_ALLOWED) {
            return new RouterResult(RouterResult::STATUS_NOT_ALLOWED);
        }

        return new RouterResult(RouterResult::STATUS_MATCH, $details[1](), $details[2]);
    }

    /**
     * Finalize routes and prepare the dispatcher
     */
    public function getDispatcher() : FastRoute\Dispatcher
    {
        if ($this->dispatcher === null) {
            $this->dispatcher = new FastRoute\Dispatcher\GroupCountBased($this->collection->getData());
        }

        return $this->dispatcher;
    }
}