<?php

declare(strict_types=1);

namespace MattColf\Flex\Controller;

use Interop\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Returns a not found response
 */
class NotFoundController implements RequestHandlerInterface
{
    /**
     * @var ResponseInterface
     */
    private $response;

    /**
     * @param ResponseInterface $response
     */
    public function __construct(ResponseInterface $response)
    {
        $this->response = $response;
    }

    public function handle(ServerRequestInterface $request) : ResponseInterface
    {
        $response = $this->response->withStatus(404);

        // @todo
        $response->getBody()->write('<h1>Not Found</h1>');

        return $response;
    }
}

