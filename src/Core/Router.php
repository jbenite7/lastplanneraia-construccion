<?php

namespace App\Core;

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;

use function FastRoute\simpleDispatcher;

class Router
{
    private $dispatcher;
    private $routes = [];

    public function add($method, $route, $handler)
    {
        $this->routes[] = [$method, $route, $handler];
    }

    public function get($route, $handler)
    {
        $this->add('GET', $route, $handler);
    }

    public function post($route, $handler)
    {
        $this->add('POST', $route, $handler);
    }

    public function dispatch()
    {
        $this->dispatcher = simpleDispatcher(function (RouteCollector $r) {
            foreach ($this->routes as $route) {
                $r->addRoute($route[0], $route[1], $route[2]);
            }
        });

        // Fetch method and URI from global state
        $httpMethod = $_SERVER['REQUEST_METHOD'];
        $uri = $_SERVER['REQUEST_URI'];

        // Strip query string (?foo=bar) and decode URI
        if (false !== $pos = strpos($uri, '?')) {
            $uri = substr($uri, 0, $pos);
        }
        $uri = rawurldecode($uri);

        $routeInfo = $this->dispatcher->dispatch($httpMethod, $uri);

        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
                ErrorPage::render(
                    404,
                    'Esta página no existe',
                    'La dirección que abriste no corresponde a ninguna pantalla del producto. Puede que el enlace esté viejo o mal copiado.'
                );
                break;
            case Dispatcher::METHOD_NOT_ALLOWED:
                ErrorPage::render(
                    405,
                    'Esa acción no se puede hacer aquí',
                    'La pantalla existe, pero no acepta este tipo de petición. Suele pasar al recargar tras enviar un formulario.'
                );
                break;
            case Dispatcher::FOUND:
                $handler = $routeInfo[1];
                $vars = $routeInfo[2];

                // If handler is an array [Controller::class, 'method']
                if (is_array($handler)) {
                    $controllerName = $handler[0];
                    $method = $handler[1];
                    $controller = new $controllerName();
                    call_user_func_array([$controller, $method], $vars);
                } else {
                    // If handler is a callable/closure
                    call_user_func_array($handler, $vars);
                }
                break;
        }
    }
}
