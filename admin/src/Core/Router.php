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
            'handler' => $handler,
        ];
    }

    /**
     * Dispatch the current request.
     */
    public function dispatch()
    {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        error_log("Router Dispatch - Original URI: " . $_SERVER['REQUEST_URI']);

        // Remove base paths used in local and Docker deployments.
        $uri = preg_replace('#^/admin/public/index\.php#', '', $uri);
        $uri = preg_replace('#^/admin/public#', '', $uri);
        $uri = preg_replace('#^/admin#', '', $uri);

        if ($uri === '') {
            $uri = '/';
        } elseif ($uri[0] !== '/') {
            $uri = '/' . $uri;
        }

        error_log("Router Dispatch - Processed URI: " . $uri);

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
