<?php

declare(strict_types=1);

namespace MattColf\Flex;
use Throwable;
use MattColf\Flex\Route\RouteDetails;
use MattColf\Flex\Container\ContainerProxy;
use MattColf\Flex\Container\ContainerFactory;
use MattColf\Flex\Utility\CallableResolver;
use MattColf\Flex\Utility\ConfigTrait;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class App
{
    use ConfigTrait;

    // Config Keys
    const DEBUG = 'debug';
    const ROUTES = 'routes';
    const MIDDLEWARE = 'middleware';

    // Config Defaults
    const DEFAULT_DEBUG = false;
    const DEFAULT_ROUTES = [];
    const DEFAULT_MIDDLEWARE = [];

    // Request Attributes
    const ROUTE_NAME = 'route_name';
    const ROUTE_PARAMS = 'route_params';
    const ROUTE_STATUS = 'route_status';

    /**
     * @var ContainerProxy
     */
    private $container;

    /**
     * @var callable[]
     */
    private $middleware;

    /**
     * Create an application with a default container
     *
     * @param array $config
     * @return App
     */
    public static function create(array $config = []) : App
    {
        return new static(ContainerFactory::create($config), $config);
    }

    /**
     * @param ContainerInterface $container
     * @param array $config
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function __construct(ContainerInterface $container, array $config = [])
    {
        $this->setConfig($config, [
            static::DEBUG => static::DEFAULT_DEBUG,
            static::ROUTES => static::DEFAULT_ROUTES,
            static::MIDDLEWARE => static::DEFAULT_MIDDLEWARE
        ]);

        $this->container = new ContainerProxy($container);

        $this->middleware = array_map(function ($reference) {
            return CallableResolver::resolve($this->container, $reference);
        }, $this->getConfig(static::MIDDLEWARE));

        $this->container->getRouteLoader()->load($this->getConfig(static::ROUTES));
    }

    public function run() : ResponseInterface
    {
        //$whoops = $this->container->getWhoops();
        //$whoops->register();

        $writer = $this->container->getWriter();
        $writer->start();

        try {
            $response = $this->execute($this->container->getRequest(), $this->container->getResponse());
        } catch (Throwable $error) {
            throw $error;
            //$response = $this->container->getErrorHandler()->handle($error);
        }

        $writer->finalize($response);

        return $response;
    }

    /**
     * Resolve the route and execute the stack
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function execute(ServerRequestInterface $request, ResponseInterface $response) : ResponseInterface
    {
        $route = $this->container->getRouter()->resolve($request);
        $dispatcher = $this->container->getDispatcher();

        $request = $request->withAttribute(static::ROUTE_NAME, $route->getName());
        $request = $request->withAttribute(static::ROUTE_STATUS, $route->getStatus());
        $request = $request->withAttribute(static::ROUTE_PARAMS, $route->getParams());

        if ($route->getStatus() === RouteDetails::STATUS_NOT_FOUND) {
            $controller = $this->container->getNotFoundController();
            return $dispatcher->dispatch($request, $response, array_merge($this->middleware, [$controller]));
        }

        if ($route->getStatus() === RouteDetails::STATUS_NOT_ALLOWED) {
            $controller = $this->container->getNotAllowedController();
            return $dispatcher->dispatch($request, $response, array_merge($this->middleware, [$controller]));
        }

        return $dispatcher->dispatch($request, $response, array_merge($this->middleware, $route->getStack()));
    }
}
