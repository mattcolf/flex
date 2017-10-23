<?php

declare(strict_types=1);

namespace MattColf\Flex\Route\Stack;

use Interop\Http\Server\MiddlewareInterface;
use Interop\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Proxies calls to a lazy loaded middleware object
 */
class DeferredMiddleware implements MiddlewareInterface
{
    /**
     * @var callable
     */
    private $proxy;

    /**
     * @param callable $proxy
     */
    public function __construct(callable $proxy)
    {
        $this->proxy = $proxy;
    }

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) : ResponseInterface
    {
        return call_user_func($this->proxy, $request, $handler);
    }
}