<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Command;

use Mini\Console\Table;
use Mini\Service\HttpServer\RouteService;
use Mini\Support\Command;
use Swoole\Process;

class RoutesAllCommandService extends AbstractCommandService
{
    protected array $wsRoutes = [];
    protected array $httpRoutes = [];

    /**
     * @param Process $process
     * @return void
     */
    public function handle(Process $process): void
    {
        $routes = RouteService::getInstance()->routes();
        $this->parseWebSocketRoutes($routes['ws']);
        $this->parseHttpRoutes($routes['http']);
        Command::line();
        if (empty($this->wsRoutes)) {
            $wsRoutes = [[
                'Url' => '[empty]',
                'Handler' => '[empty]'
            ]];
        } else {
            $wsRoutes = [];
            foreach ($this->wsRoutes as $wsRoute) {
                $wsRoutes[] = [
                    'Url' => '<underscore>' . $wsRoute['url'] . '</underscore>',
                    'Handler' => is_string($wsRoute['handler']) ? '<light_blue>' . $wsRoute['handler'] . '</light_blue>' : (is_callable($wsRoute['handler']) ? '<yellow>Callable</yellow>' : '<red>Error: </red>' . ucfirst(gettype($wsRoute['handler'])))
                ];
            }
        }
        Table::show($wsRoutes, "Websocket Routes");
        if (empty($this->httpRoutes)) {
            $httpRoutes = [[
                'Url' => '[empty]',
                'Method' => '[empty]',
                'Handler' => '[empty]'
            ]];
        } else {
            $httpRoutes = [];
            foreach ($this->httpRoutes as $wsRoute) {
                $method = strtoupper($wsRoute['method']);
                switch ($method) {
                    case 'GET':
                        $method = '<green>' . $method . '</green>';
                        break;
                    case 'POST':
                        $method = '<yellow>' . $method . '</yellow>';
                        break;
                    case 'DELETE':
                        $method = '<red>' . $method . '</red>';
                        break;
                    case 'PUT':
                        $method = '<cyan>' . $method . '</cyan>';
                        break;
                    case 'ANY':
                        $method = '<mga>' . $method . '</mga>';
                        break;
                }
                $httpRoutes[] = [
                    'Url' => '<underscore>' . $wsRoute['url'] . '</underscore>',
                    'Method' => $method,
                    'Handler' => is_string($wsRoute['handler']) ? '<light_blue>' . $wsRoute['handler'] . '</light_blue>' : (is_callable($wsRoute['handler']) ? '<yellow>Callable</yellow>' : '<red>Error: </red>' . ucfirst(gettype($wsRoute['handler'])))
                ];
            }
        }
        Table::show($httpRoutes, 'Http Routes');
    }

    private function parseWebSocketRoutes($wsRoutes, array $prefix = [], array $namespace = []): void
    {
        foreach ($wsRoutes as $group => $route) {
            if (!is_array($route)) {
                continue;
            }
            if (is_string($group)) {
                $explodeGroup = explode('#', $group, 2);
                if (isset($explodeGroup[1])) {
                    $namespace[] = $explodeGroup[1];
                }
                $prefix[] = trim($explodeGroup[0], '/');
                $this->parseWebSocketRoutes($route, $prefix, $namespace);
                if (isset($explodeGroup[1])) {
                    array_pop($namespace);
                    array_pop($prefix);
                }
            } else if (isset($route[0]) && is_string($route[0])) {
                $namespaceString = !empty($namespace) ? implode('\\', $namespace) . '\\' : '';
                $this->wsRoutes[] = [
                    'url' => !empty($prefix) ? '/' . implode('/', $prefix) . '/' . trim($route[0], '/') : '/' . trim($route[0], '/'),
                    'handler' => is_string($route[1]) ? $namespaceString . $route[1] : $route[1]
                ];
            }
        }
    }

    private function parseHttpRoutes($wsRoutes, array $prefix = [], array $namespace = []): void
    {
        foreach ($wsRoutes as $group => $route) {
            if (!is_array($route)) {
                continue;
            }
            if (is_string($group)) {
                $explodeGroup = explode('#', $group, 2);
                if (isset($explodeGroup[1])) {
                    $namespace[] = $explodeGroup[1];
                }
                $prefix[] = trim($explodeGroup[0], '/');
                $this->parseHttpRoutes($route, $prefix, $namespace);
                if (isset($explodeGroup[1])) {
                    array_pop($namespace);
                }
                array_pop($prefix);
            } else if (isset($route[0]) && is_string($route[0])) {
                $namespaceString = !empty($namespace) ? implode('\\', $namespace) . '\\' : '';
                $this->httpRoutes[] = [
                    'method' => $route[0],
                    'url' => !empty($prefix) ? '/' . implode('/', $prefix) . '/' . trim($route[1], '/') : '/' . trim($route[1], '/'),
                    'handler' => is_string($route[2]) ? $namespaceString . $route[2] : $route[2]
                ];
            }
        }
    }

    public function getCommand(): string
    {
        return 'route:all';
    }

    public function getCommandDescription(): string
    {
        return 'show all routes.';
    }
}