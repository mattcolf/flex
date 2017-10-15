<?php

declare(strict_types=1);

namespace MattColf\Flex\Error;

use Throwable;
use Psr\Http\Message\ResponseInterface;

interface ErrorHandlerInterface
{
    public function handle(Throwable $error) : ResponseInterface;
}