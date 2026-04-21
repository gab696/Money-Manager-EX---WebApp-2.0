<?php
declare(strict_types=1);

namespace App;

final class Router
{
    /** @var array<string, array<int, array{0:string, 1:callable|array}>> */
    private array $routes = ['GET' => [], 'POST' => []];

    public function get(string $pattern, callable|array $handler): void
    {
        $this->routes['GET'][] = [$pattern, $handler];
    }

    public function post(string $pattern, callable|array $handler): void
    {
        $this->routes['POST'][] = [$pattern, $handler];
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = (string) parse_url($uri, PHP_URL_PATH);
        $base = Config::baseUrl();
        if ($base !== '' && str_starts_with($path, $base)) {
            $path = substr($path, strlen($base));
        }
        if ($path === '' || $path === false) {
            $path = '/';
        }
        $method = $method === 'HEAD' ? 'GET' : $method;

        foreach ($this->routes[$method] ?? [] as [$pattern, $handler]) {
            $regex = '#^' . preg_replace('#\{(\w+)\}#', '(?<$1>[^/]+)', $pattern) . '$#';
            if (preg_match($regex, $path, $m)) {
                $params = [];
                foreach ($m as $k => $v) {
                    if (is_string($k)) {
                        $params[$k] = $v;
                    }
                }
                $this->call($handler, $params);
                return;
            }
        }

        http_response_code(404);
        echo View::render('errors/404');
    }

    private function call(callable|array $handler, array $params): void
    {
        if (is_array($handler)) {
            [$cls, $fn] = $handler;
            $instance = new $cls();
            $instance->$fn($params);
            return;
        }
        $handler($params);
    }
}
