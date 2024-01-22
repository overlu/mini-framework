<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Database\Mini;

use Mini\Contracts\MiniDB;
use Mini\Service\AbstractServiceProvider;

class MiniDBServiceProvider extends AbstractServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('db.mini.pool', function () {
            return new Pool();
        });
        $this->app->singleton(MiniDB::class, function () {
            return new DB();
        });
        $this->app->alias(MiniDB::class, 'db.mini');
    }

    public function boot(): void
    {
    }
}
