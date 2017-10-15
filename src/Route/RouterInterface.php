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
     * @param string $name
     * @param string|string[] $method
     * @param string $path
     * @param callable[] $stack
     * @param array $options
     * @return void
     * @throws InvalidArgumentException
     */
    public function addRoute(string $name, $method, string $path, array $stack, array $options = []) : void;

    /**
     * Resolve the current route
     *
     * @param ServerRequestInterface $request
     * @return RouteDetails
     */
    public function resolve(ServerRequestInterface $request) : RouteDetails;
}