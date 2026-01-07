<?php

namespace Admin\Core;

class Router
{
    private $routes = [];

    /**
     * Add a route to the router.
     *
     * @param string $method GET, POST, etc.
     * @param string $path The URL path (e.g., '/users')
     * @param string $handler The controller and action (e.g., 'UserController@index')
     */
    public function add($method, $path, $handler)
    {
        $this->routes[] = [
            'method'  => $method,
            'path'    => $path,
            'handler' => $handler
        ];
    }

    /**
     * Dispatch the current request.
     */
    public function dispatch()
    {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        // Remove the base path if necessary (e.g., /admin)
        $uri = str_replace('/admin', '', $uri);
        if ($uri === '') $uri = '/';

        $method = $_SERVER['REQUEST_METHOD'];

        foreach ($this->routes as $route) {
            if ($route['method'] === $method && $route['path'] === $uri) {
                $this->executeHandler($route['handler']);
                return;
            }
        }

        // 404 Not Found
        http_response_code(404);
        echo "404 - Página no encontrada";
    }

    private function executeHandler($handler)
    {
        list($controllerName, $method) = explode('@', $handler);
        $fullControllerName = "Admin\\Controllers\\$controllerName";

        if (class_exists($fullControllerName)) {
            $controller = new $fullControllerName();
            if (method_exists($controller, $method)) {
                $controller->$method();
            } else {
                echo "Error: El método $method no existe en $controllerName";
            }
        } else {
            echo "Error: La clase $fullControllerName no existe";
        }
    }
}
