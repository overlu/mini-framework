<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Service\Server;

/**
 * Class TaskServer
 * @package Mini\Service\Server
 */
class TaskServer
{
    protected array $setting = [
        'task_worker_num' => 2,
        'task_max_request' => 0,
        'task_enable_coroutine' => true,
        
    ];
}