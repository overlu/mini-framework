<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Command;

use Mini\Console\Table;
use Swoole\Process;

class RoutesAllCommandService extends AbstractCommandService
{
    protected array $wsRoutes = [];
    protected array $httpRoutes = [];

    /**
     * @param Process|null $process
     * @return bool
     */
    public function handle(?Process $process): bool
    {
        $routes = app('route')->routes();
        $this->parseWebSocketRoutes($routes['ws']);
        $this->parseHttpRoutes($routes['http']);
        $this->line();
        $defaultRoute = [[
            'Handler' => empty($routes['default']) ? '<red>404</red>' : $this->parasHandler($routes['default'])
        ]];
        $this->info('  Default Route:');
        Table::show($defaultRoute, '');
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
                    'Handler' => $this->parasHandler($wsRoute['handler'])
                ];
            }
        }
        $this->info('  Websocket Routes:');
        Table::show($wsRoutes, '');
        if (empty($this->httpRoutes)) {
            $httpRoutes = [[
                'Url' => '[empty]',
                'Method' => '[empty]',
                'Handler' => '[empty]'
            ]];
        } else {
            $httpRoutes = [];
            foreach ($this->httpRoutes as $httpRoute) {
                $method = strtoupper($httpRoute['method']);
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
                    'Url' => '<underscore>' . $httpRoute['url'] . '</underscore>',
                    'Method' => $method,
                    'Handler' => $this->parasHandler($httpRoute['handler'])
                ];
            }
        }
        $this->info('  Http Routes:');
        Table::show($httpRoutes, '');

        return true;
    }

    /**
     * @param $handler
     * @return string
     */
    private function parasHandler($handler): string
    {
        return is_string($handler) ? '<light_blue>' . $handler . '</light_blue>' : (is_callable($handler) ? '<yellow>Callable</yellow>' : '<red>Error: </red>' . ucfirst(gettype($handler)));
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
                if (is_string($route[1])) {
                    $handler = $namespaceString . $route[1];
                    if (class_exists($handler)) {
                        $handler = '\\' . trim($handler, '\\');
                    }
                } else {
                    $handler = $route[1];
                }
                $this->wsRoutes[] = [
                    'url' => !empty($prefix) ? '/' . implode('/', $prefix) . '/' . trim($route[0], '/') : '/' . trim($route[0], '/'),
                    'handler' => $handler
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
                if (is_string($route[2])) {
                    $handler = $namespaceString . $route[2];
                    if (class_exists($handler)) {
                        $handler = '\\' . trim($handler, '\\');
                    }
                } else {
                    $handler = $route[2];
                }
                $this->httpRoutes[] = [
                    'method' => $route[0],
                    'url' => !empty($prefix) ? '/' . implode('/', $prefix) . '/' . trim($route[1], '/') : '/' . trim($route[1], '/'),
                    'handler' => $handler
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