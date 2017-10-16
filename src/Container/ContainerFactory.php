<?php

declare(strict_types=1);

namespace MattColf\Flex\Container;

use FastRoute;
use League\Container\Container;
use MattColf\Flex\Error\Controller\NotAllowedController;
use MattColf\Flex\Error\Controller\NotFoundController;
use MattColf\Flex\Error\ErrorHandlerInterface;
use MattColf\Flex\Error\Handler\WhoopsErrorHandler;
use MattColf\Flex\Http\ControllerInterface;
use MattColf\Flex\Http\Writer\BufferedWriter;
use MattColf\Flex\Http\WriterInterface;
use MattColf\Flex\Route\Dispatcher;
use MattColf\Flex\Route\RouteLoader;
use MattColf\Flex\Route\Router\FastRouteRouter;
use MattColf\Flex\Route\RouterInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Whoops\Run;
use Whoops\RunInterface;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequestFactory;

class ContainerFactory
{
    /**
     * Create a new container with all default values
     *
     * @param array $config
     * @return ContainerInterface
     */
    public static function create(array $config = []) : ContainerInterface
    {
        $container = new Container();

        //
        // Classes
        //

        $container->add(ContainerProxy::WHOOPS, function () {
            return static::createWhoops();
        }, true);

        $container->add(ContainerProxy::DISPATCHER, function () {
            return static::createDispatcher();
        }, true);

        $container->add(ContainerProxy::ROUTE_LOADER, function () use ($container) {
            return static::createRouteLoader($container);
        }, true);

        $container->add(ContainerProxy::CONTROLLER_NOT_FOUND, function () {
            return static::createNotFoundController();
        }, true);

        $container->add(ContainerProxy::CONTROLLER_NOT_ALLOWED, function () {
            return static::createNotAllowedController();
        }, true);

        //
        // Interfaces Implementations
        //

        $container->add(ContainerProxy::ROUTER, function () {
            return static::createRouter();
        }, true);

        $container->add(ContainerProxy::WRITER, function () {
            return static::createWriter();
        }, true);

        $container->add(ContainerProxy::REQUEST, function () {
            return static::createRequest();
        }, true);

        $container->add(ContainerProxy::RESPONSE, function () {
            return static::createResponse();
        }, true);

        $container->add(ContainerProxy::ERROR_HANDLER, function () use ($container) {
            return static::createErrorHandler($container);
        }, true);


        return $container;
    }

    public static function createRouter() : RouterInterface
    {
        return new FastRouteRouter(static::createFastRouteCollection());
    }

    public static function createWriter() : WriterInterface
    {
        return new BufferedWriter();
    }

    public static function createRequest() : ServerRequestInterface
    {
        return ServerRequestFactory::fromGlobals();
    }

    public static function createResponse() : ResponseInterface
    {
        return new Response();
    }

    public static function createDispatcher() : Dispatcher
    {
        return new Dispatcher();
    }

    public static function createRouteLoader(ContainerInterface $container) : RouteLoader
    {
        return new RouteLoader($container, $container->get(ContainerProxy::ROUTER));
    }

    public static function createErrorHandler(ContainerInterface $container) : ErrorHandlerInterface
    {
        return new WhoopsErrorHandler($container->get(ContainerProxy::WHOOPS));
    }

    public static function createWhoops() : RunInterface
    {
        return new Run();
    }

    public static function createNotFoundController() : ControllerInterface
    {
        return new NotFoundController();
    }

    public static function createNotAllowedController() : ControllerInterface
    {
        return new NotAllowedController();
    }

    public static function createFastRouteCollection() : FastRoute\RouteCollector
    {
        $parser = new FastRoute\RouteParser\Std();
        $generator = new FastRoute\DataGenerator\GroupCountBased();

        return new FastRoute\RouteCollector($parser, $generator);
    }
}