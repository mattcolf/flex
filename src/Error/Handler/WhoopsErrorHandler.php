<?php

declare(strict_types=1);

namespace MattColf\Flex\Error\Handler;

use Psr\Http\Message\ResponseInterface;
use Throwable;
use Whoops\RunInterface;
use MattColf\Flex\Error\ErrorHandlerInterface;

class WhoopsErrorHandler implements ErrorHandlerInterface
{
    private $whoops;

    public function __construct(RunInterface $whoops)
    {
        $this->whoops = $whoops;
    }

    public function handle(Throwable $error) : ResponseInterface
    {
        $this->whoops->handleException($error);

        // @todo
        die();
    }
}