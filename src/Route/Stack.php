<?php

declare(strict_types=1);

namespace MattColf\Flex\Route;

use InvalidArgumentException;
use Interop\Http\Server\MiddlewareInterface;
use Interop\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Handles executing the stack
 */
class Stack implements RequestHandlerInterface
{
    const ERR_ITEM_TYPE = 'Unsupported stack item type. %s';

    /**
     * @var RequestHandlerInterface
     */
    private $default;

    /**
     * @var array
     */
    private $stack;

    /**
     * @param RequestHandlerInterface $default
     * @param array $stack
     */
    public function __construct(RequestHandlerInterface $default, array $stack = [])
    {
        $this->default = $default;
        $this->stack = $stack;
    }

    /**
     * Run the stack
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request) : ResponseInterface
    {
        $remaining = $this->stack;

        if (count($remaining) === 0) {
            return $this->default->handle($request);
        }

        $current = array_shift($remaining);

        if ($current instanceof MiddlewareInterface) {
            return $current->process($request, new static($this->default, $remaining));
        }

        if ($current instanceof RequestHandlerInterface) {
            return $current->handle($request);
        }

        throw new InvalidArgumentException(sprintf(static::ERR_ITEM_TYPE, var_export($current, true)));
    }
}