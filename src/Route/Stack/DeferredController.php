<?php

declare(strict_types=1);

namespace MattColf\Flex\Route\Stack;

use Interop\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Proxies calls to a lazy loaded controller object
 */
class DeferredController implements RequestHandlerInterface
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
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request) : ResponseInterface
    {
        return call_user_func($this->proxy, $request);
    }
}