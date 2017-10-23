<?php

declare(strict_types=1);

namespace MattColf\Flex;

use MattColf\Flex\Route\Stack;
use Throwable;
use MattColf\Flex\Route\Route;
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
    const ROUTE = 'route';
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

        $this->middleware = $this->getConfig(static::MIDDLEWARE);

        $this->container = new ContainerProxy($container);
        $this->container->getRouteLoader()->load($this->getConfig(static::ROUTES));
    }

    public function run() : ResponseInterface
    {
        //$whoops = $this->container->getWhoops();
        //$whoops->register();

        $writer = $this->container->getWriter();
        $writer->start();

        try {
            $response = $this->execute($this->container->getRequest());
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
     * @return ResponseInterface
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function execute(ServerRequestInterface $request) : ResponseInterface
    {
        $result = $this->container->getRouter()->resolve($request);
        $stack = $this->middleware;

        if ($result->isMatch()) {
            $stack = array_merge($stack, $result->getRoute()->getStack());
        }

        if ($result->isNotFound()) {
            $stack = array_merge($stack, [$this->container->getNotFoundController()]);
        }

        if ($result->isNotAllowed()) {
            $stack = array_merge($stack, [$this->container->getNotAllowedController()]);
        }

        $stack = new Stack($this->container->getDefaultController(), $stack);

        $request = $this->container->getRequest();
        $request = $request->withAttribute(static::ROUTE, $result->getRoute());
        $request = $request->withAttribute(static::ROUTE_STATUS, $result->getStatus());
        $request = $request->withAttribute(static::ROUTE_PARAMS, $result->getParams());

        return $stack->handle($request);
    }
}
