<?php

declare(strict_types=1);

namespace MattColf\Flex\Route;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class Dispatcher
{
    /**
     * Dispatch the request to the stack
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param array $stack
     * @return ResponseInterface
     */
    public function dispatch(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $stack
    ) : ResponseInterface {
        $stack = $this->resolve($stack);

        return $stack($request, $response);
    }

    /**
     * Recursively resolve the stack
     *
     * @param callable[] $stack
     * @return callable
     */
    private function resolve(array $stack) : callable
    {
        if (count($stack) === 0) {
            return function (ServerRequestInterface $request, ResponseInterface $response) {
                return $response;
            };
        }

        $current = array_shift($stack);

        return function (ServerRequestInterface $request, ResponseInterface $response) use ($current, $stack) {
            return $current($request, $response, $this->resolve($stack));
        };
    }
}