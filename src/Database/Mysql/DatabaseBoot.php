<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Database\Mysql;

use Mini\Container\Container;
use Mini\Contracts\Container\BindingResolutionException;
use Mini\Database\Mysql\Capsule\Manager;
use Mini\Events\Dispatcher;
use Mini\Database\Mysql\Eloquent\Model;

class DatabaseBoot
{
    /**
     * @var array
     */
    protected array $config = [
        'driver' => 'mysql',
        'host' => 'localhost',
        'port' => 3306,
        'database' => 'test',
        'username' => 'root',
        'password' => 'root',
        'charset' => 'utf8mb4',
        'options' => [],
        'size' => 64,
    ];

    /**
     * DatabaseBoot constructor.
     * @param array $config
     * @throws BindingResolutionException
     */
    public function __construct(array $config)
    {
        Model::clearBootedModels();
        $app = app();
        $capsule = new Manager();
//        $capsule->setEventDispatcher(new Dispatcher(new Container));
        $capsule->setEventDispatcher(app('events'));
        foreach ($config as $key => $conf) {
            $conf = array_replace_recursive($this->config, $conf);
            $capsule->addConnection($conf, $key);
        }
        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        $app->singleton('db', function () use ($capsule) {
            return $capsule->getDatabaseManager();
        });
    }
}
