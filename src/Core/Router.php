<?php
namespace App\Core;

class Router {
    private $routes = [];

    public function get($path, $callback) { $this->addRoute('GET', $path, $callback); }
    public function post($path, $callback) { $this->addRoute('POST', $path, $callback); }
    public function put($path, $callback) { $this->addRoute('PUT', $path, $callback); }
    public function delete($path, $callback) { $this->addRoute('DELETE', $path, $callback); }

    private function addRoute($method, $path, $callback) {
        // Convertimos {id} en una expresión regular ([0-9]+)
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '([0-9]+)', $path);
        $pattern = "#^" . $pattern . "$#";
        $this->routes[$method][$pattern] = $callback;
    }

    public function dispatch() {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        if (isset($this->routes[$method])) {
            foreach ($this->routes[$method] as $pattern => $callback) {
                if (preg_match($pattern, $path, $matches)) {
                    array_shift($matches); // Quitamos la coincidencia completa, dejamos solo los parámetros

                    if (is_callable($callback)) {
                        call_user_func_array($callback, $matches);
                    } elseif (is_array($callback)) {
                        $controllerName = $callback[0];
                        $methodName = $callback[1];
                        $controller = new $controllerName();
                        call_user_func_array([$controller, $methodName], $matches);
                    }
                    return;
                }
            }
        }

        // Si llegamos aquí, no hubo coincidencia
        header("HTTP/1.0 404 Not Found");
        echo json_encode(['error' => 'Ruta no encontrada', 'path' => $path]);
    }
}