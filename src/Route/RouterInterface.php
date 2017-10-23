<?php

declare(strict_types=1);

namespace MattColf\Flex\Route;

use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;

interface RouterInterface
{
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
    public function relativeUrlFor(string $route, array $params = [], array $query = []) : string;

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
    public function absoluteUrlFor(ServerRequestInterface $request, string $route, array $params = [], array $query = []) : string;

    /**
     * Add a single route to the router
     *
     * @param Route $route
     * @throws InvalidArgumentException
     */
    public function addRoute(Route $route) : void;

    /**
     * Get a route by name
     *
     * @param string $name
     * @return Route|null
     */
    public function getRoute(string $name) : ?Route;

    /**
     * Resolve the current route
     *
     * @param ServerRequestInterface $request
     * @return RouterResult
     */
    public function resolve(ServerRequestInterface $request ) : RouterResult;
}