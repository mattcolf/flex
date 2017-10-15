<?php

declare(strict_types=1);

namespace MattColf\Flex\Error\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use MattColf\Flex\Http\ControllerInterface;

class NotFoundController implements ControllerInterface
{
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response) : ResponseInterface
    {
        // @todo
        $response = $response->withStatus(404);
        $response->getBody()->write('<h1>Not Found</h1>');

        return $response;
    }
}

