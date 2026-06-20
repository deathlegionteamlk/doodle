<?php
/**
 * doodle - Simple front-controller router
 *
 * URL format: index.php?r=Controller/method/params...
 * Or with mod_rewrite: /Controller/method/params...
 *
 * @package doodle
 * @author  Death Legion Team
 */

class Router
{
    private string $controller = 'Home';
    private string $method     = 'index';
    private array  $params     = [];

    public function dispatch(): void
    {
        // Determine route from URL
        $url = $this->parseUrl();

        // Controller
        if (!empty($url[0])) {
            $ctrl = ucfirst($url[0]);
            if (preg_match('/^[A-Za-z]+$/', $ctrl)) {
                $this->controller = $ctrl;
            }
            array_shift($url);
        }

        // Method
        if (!empty($url[0])) {
            $m = $url[0];
            if (preg_match('/^[A-Za-z][A-Za-z0-9_]*$/', $m) && $m !== 'index' || (preg_match('/^[A-Za-z][A-Za-z0-9_]*$/', $m) && $m !== '__construct')) {
                $this->method = $m;
            }
            array_shift($url);
        }

        // Remaining params
        $this->params = $url;

        // Load controller
        $ctrlClass = $this->controller . 'Controller';
        $file = APP_DIR . '/controllers/' . $ctrlClass . '.php';
        if (!file_exists($file)) {
            $this->notFound('Controller not found: ' . $ctrlClass);
            return;
        }
        require_once $file;
        if (!class_exists($ctrlClass)) {
            $this->notFound('Class not found: ' . $ctrlClass);
            return;
        }
        $controllerObj = new $ctrlClass();
        if (!method_exists($controllerObj, $this->method) || !is_callable([$controllerObj, $this->method])) {
            $this->notFound('Method not found: ' . $ctrlClass . '::' . $this->method);
            return;
        }

        // Verify CSRF on POST/PUT/DELETE
        if (in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['POST', 'PUT', 'DELETE'])) {
            if (!CSRF::verify()) {
                http_response_code(419);
                die('CSRF token mismatch. Please go back, refresh the page, and try again.');
            }
        }

        call_user_func_array([$controllerObj, $this->method], $this->params);
    }

    private function parseUrl(): array
    {
        $route = '';
        if (isset($_GET['r'])) {
            $route = $_GET['r'];
        } elseif (isset($_SERVER['PATH_INFO'])) {
            $route = ltrim($_SERVER['PATH_INFO'], '/');
        } elseif (isset($_SERVER['REQUEST_URI'])) {
            $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            $script = dirname($_SERVER['SCRIPT_NAME']);
            if ($script !== '/' && strpos($uri, $script) === 0) {
                $uri = substr($uri, strlen($script));
            }
            $route = ltrim($uri, '/');
            // Skip index.php in path
            if (strpos($route, 'index.php') === 0) {
                $route = ltrim(substr($route, 9), '/');
            }
        }
        $route = trim($route, '/');
        if (empty($route)) return [];
        // Sanitize
        $parts = explode('/', $route);
        return array_values(array_filter($parts, fn($p) => $p !== ''));
    }

    private function notFound(string $msg = 'Page not found'): void
    {
        http_response_code(404);
        if (file_exists(APP_DIR . '/views/errors/404.php')) {
            view('errors/404', ['message' => $msg]);
        } else {
            echo '<h1>404 Not Found</h1><p>' . htmlspecialchars($msg) . '</p>';
        }
    }
}
