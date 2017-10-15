<?php

declare(strict_types=1);

namespace MattColf\Flex\Container;

use MattColf\Flex\Error\ErrorHandlerInterface;
use MattColf\Flex\Http\ControllerInterface;
use MattColf\Flex\Http\WriterInterface;
use MattColf\Flex\Route\Dispatcher;
use MattColf\Flex\Route\RouteLoader;
use MattColf\Flex\Route\RouterInterface;
use MattColf\Flex\Utility\TypeValidation;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Whoops\RunInterface;

/**
 * A proxy for the service container that ensures that the user configured it correctly. If the user did
 * not configure the correct service types, an InvalidArgumentException will be thrown.
 */
class ContainerProxy implements ContainerInterface
{
    // Container Keys
    const ROUTER = 'router';
    const WRITER = 'writer';
    const WHOOPS = 'whoops';
    const REQUEST = 'request';
    const RESPONSE = 'response';
    const DISPATCHER = 'dispatcher';
    const ROUTE_LOADER = 'route_loader';
    const ERROR_HANDLER = 'error_handler';
    const CONTROLLER_NOT_FOUND = 'controller_not_found';
    const CONTROLLER_NOT_ALLOWED = 'controller_not_allowed';


    // Error Messages
    const ERR_ROUTER = 'Invalid router. Must be an instance of %s.';
    const ERR_WRITER = 'Invalid writer. Must be an instance of %s.';
    const ERR_WHOOPS = 'Invalid whoops. Must be an instance of %s.';
    const ERR_REQUEST = 'Invalid request. Must be an instance of %s.';
    const ERR_RESPONSE = 'Invalid response. Must be an instance of %s.';
    const ERR_DISPATCHER = 'Invalid dispatcher. Must be an instance of %s.';
    const ERR_ROUTE_LOADER = 'Invalid route loader. Must be an instance of %s.';
    const ERR_ERROR_HANDLER = 'Invalid error handler. Must be an instance of %s.';
    const ERR_RESPONSE_EMITTER = 'Invalid response emitter. Must be an instance of %s.';
    const ERR_CONTROLLER_NOT_FOUND = 'Invalid not found controller. Must be an instance of %s.';
    const ERR_CONTROLLER_NOT_ALLOWED = 'Invalid not allowed controller. Must be an instance of %s.';

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param string $id
     * @return bool
     */
    public function has($id)
    {
        return $this->container->has($id);
    }

    /**
     * @param string $id
     * @return mixed
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function get($id)
    {
        return $this->container->get($id);
    }

    /**
     * @return RouterInterface
     * @throws ContainerExceptionInterface
     */
    public function getRouter() : RouterInterface
    {
        $router = $this->get(static::ROUTER);

        TypeValidation::validateType($router, RouterInterface::class, static::ERR_ROUTER);

        return $router;
    }

    /**
     * @return ServerRequestInterface
     * @throws ContainerExceptionInterface
     */
    public function getRequest() : ServerRequestInterface
    {
        $request = $this->get(static::REQUEST);

        TypeValidation::validateType($request, ServerRequestInterface::class, static::ERR_REQUEST);

        return $request;
    }

    /**
     * @return ResponseInterface
     * @throws ContainerExceptionInterface
     */
    public function getResponse() : ResponseInterface
    {
        $response = $this->get(static::RESPONSE);

        TypeValidation::validateType($response, ResponseInterface::class, static::ERR_RESPONSE);

        return $response;
    }

    /**
     * @return RouteLoader
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getRouteLoader() : RouteLoader
    {
        $loader = $this->get(static::ROUTE_LOADER);

        TypeValidation::validateType($loader, RouteLoader::class, static::ERR_ROUTE_LOADER);

        return $loader;
    }

    /**
     * @return Dispatcher
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getDispatcher() : Dispatcher
    {
        $dispatcher = $this->get(static::DISPATCHER);

        TypeValidation::validateType($dispatcher, Dispatcher::class, static::ERR_DISPATCHER);

        return $dispatcher;
    }

    /**
     * @return ControllerInterface
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getNotFoundController() : ControllerInterface
    {
        $controller = $this->get(static::CONTROLLER_NOT_FOUND);

        TypeValidation::validateType($controller, ControllerInterface::class, static::ERR_CONTROLLER_NOT_FOUND);

        return $controller;
    }

    /**
     * @return ControllerInterface
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getNotAllowedController() : ControllerInterface
    {
        $controller = $this->get(static::CONTROLLER_NOT_ALLOWED);

        TypeValidation::validateType($controller, ControllerInterface::class, static::ERR_CONTROLLER_NOT_ALLOWED);

        return $controller;
    }

    /**
     * @return ErrorHandlerInterface
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getErrorHandler() : ErrorHandlerInterface
    {
        $handler = $this->get(static::ERROR_HANDLER);

        TypeValidation::validateType($handler, ErrorHandlerInterface::class, static::ERR_ERROR_HANDLER);

        return $handler;
    }

    /**
     * @return WriterInterface
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getWriter() : WriterInterface
    {
        $writer = $this->get(static::WRITER);

        TypeValidation::validateType($writer, WriterInterface::class,static::ERR_WRITER);

        return $writer;
    }

    /**
     * @return RunInterface
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getWhoops() : RunInterface
    {
        $whoops = $this->get(static::WHOOPS);

        TypeValidation::validateType($whoops, RunInterface::class, static::ERR_WHOOPS);

        return $whoops;
    }
}