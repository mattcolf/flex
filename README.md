Flex
====

## WARNING: This framework is under active development and is not yet ready for use in production.

A simple stack based framework that's easy to use and extend. Flex uses PSR-7 requests and
responses, the PSR-11 container, and other community libraries like Fast Route, Zend Diactoros, 
League Container, and Whoops. 

## Why Flex?
Flex is a micro framework that stays out of your way and provides a minimal wrapper that allows 
you to write simple framework agnostic application code. Routing is based on a simple stack of 
application code whose only interaction with the framework is through PSR-7 request and
response object.

### Component Replacement
All framework components can be replaced to suit the needs of the application by modifying
the dependency injection container that is passed to Flex.

#### Container
todo

#### Request & Response
todo

#### Router
todo

#### Not Found & Not Allowed Controllers
todo

#### Error Handler
todo

#### Writer
todo

## Getting Started
Getting started with Flex is as simple as creating an instance of the Flux App and calling 
the run method. If you don't need to modify the container you can simply call the 
`App::create()` method to setup the application with a default container.

```php
<?php

use MattColf\Flex\App;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

$config = [];
$config['debug'] = true;

$config['routes'] = [
    'index' => [
        'method' => 'GET',
        'path' => '/',
        'stack' => [
            function (ServerRequestInterface $request, ResponseInterface $response) : ResponseInterface
            {
                $response->getBody()->write('<h1>Hello World!</h1>');
               
                return $response;
            }
        ]
    ]
];


$app = App::create($config);
$app->run();
```

## Adding Routes

In Flex, routes are defined through the `routes` configuration key. Each entry in the `routes`
array represents either a single route or group of routes (more below) where the key is the name
of the route and the value is an array of the following route options.

- `method`: The HTTP method, or methods, that this route should match. Can be a string (`'GET'`) 
    or an array of strings (`['GET', 'POST']`). Must be a valid HTTP method (`GET`, `POST`, etc.).
- `path`: The relative path that the route should match. Should start with a `/`, like `/homepage`.
- `stack`: An array of `middleware` and `controller` references that should be run when the 
    route matches. See Stack Definition below for more details.

### Groups

When a group of routes should all have the same path prefix or middleware, you can group those
routes to simplify your configuration. Groups are configured the same as routes, but also have
the `routes` option.

- `path`: The relative path that all routes in this group must start with.
- `stack`: An array of middleware that should be added to each route in this group. This stack
    will be run before each member route's stack.
- `routes`: An array of routes that are part of this group.

Note that groups may not have a `method` option. 

### Route Parameters

Route paths may contain parameters. When the application is run, these are provided to the stack
as an array in the request object `route_params` attribute. Parameters are included in the path
as a curly bracket wrapped string. Here's an example.

```php
$routes = [
    'user-details' => [
        'method' => 'GET',
        'path' => '/users/{id}',
        'stack' => [
            'user.controller'
        ]
    ]
];
```

By default, Flex uses the Fast Route router. For more details, review the 
[documentation](https://github.com/nikic/FastRoute) on how to use route parameters there.

### Stack Members

Each route's stack defines what should be run when the route matches. It is made up of
references to middleware and controller functions that use PSR-7 request and response objects.

#### Middleware Function
Each stack may have any number of middleware functions.
```php
function (ServerRequestInterface $request, ResponseInterface $response, callable $next) : ResponseInterface
{
    // your code that should run before the rest of the stack
    
    // you are responsible for calling the next item in the stack
    $response = $next($request, $response);
    
    // your code that shiuld run after the rest of the stack
    
    return $response;
}
```

#### Controller Function
Each stack must have a single controller which must also be the last item in the stack.
```php
function (ServerRequestInterface $request, ResponseInterface $response) : ResponseInterface
{
    // your controller code

    return $response;
}
```

#### Stack References
Flex uses a reference to lookup each middleware and controller function that you add to the
stack. These references are generally either callable functions or keys to invokeavble 
classes in the dependency injection container, but more types are supported. Here are some
examples.

**Container Class Invoke**
```php
class Controller
{
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response) : ResponseInterface {
        return $response;
    };
}

$container->set('controller', new Controller());
$reference = 'controller';
```

**Container Class Method**
```php
class Controller
{
    public function run(ServerRequestInterface $request, ResponseInterface $response) : ResponseInterface {
        return $response;
    };
}

$container->set('controller', new Controller());
$reference = 'controller::run';
```

**Anonymous Function**
```php
$reference = function (ServerRequestInterface $request, ResponseInterface $response) : ResponseInterface {
    return $response;
};
```

**Object Invoke**
```php
class Controller
{
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response) : ResponseInterface {
        return $response;
    };
}

$reference = [new Controller()];
```

**Object Method Callable**
```php
class Controller
{
    public function run(ServerRequestInterface $request, ResponseInterface $response) : ResponseInterface {
        return $response;
    };
}

$reference = [new Controller(), 'run'];
```

**Static Method Callable**
```php
class Controller
{
    public function run(ServerRequestInterface $request, ResponseInterface $response) : ResponseInterface {
        return $response;
    };
}

$reference = [Controller::class, 'run'];
```

## Configuration

The following configuration values are available.

- `debug`
- `routes`
- `middleware`