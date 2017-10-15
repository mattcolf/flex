<?php

declare(strict_types=1);

namespace MattColf\Flex\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface ControllerInterface
{
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response) : ResponseInterface;
}