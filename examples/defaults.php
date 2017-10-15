<?php

require_once __DIR__ . '/../vendor/autoload.php';

use MattColf\Flex\App;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

$config = [
    'routes' => [
        'index' => [
            'method' => 'GET',
            'path' => '/',
            'stack' => [
                function (ServerRequestInterface $request, ResponseInterface $response) : ResponseInterface
                {
                    $response->getBody()->write('<h1>Hello world!</h1>');

                    return $response;
                }
            ]
        ]
    ]
];

$app = App::create($config);
$app->run();