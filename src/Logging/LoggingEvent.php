<?php
/**
 * This file is part of mini-framework.
 * @auth lupeng
 * @date 2024/5/31 16:44
 */
declare(strict_types=1);

namespace Mini\Logging;

class LoggingEvent
{
    /**
     * 日志模块
     * @var string
     */
    public string $module = '';

    /**
     * 日志内容
     * @var string
     */
    public string $message = '';

    /**
     * 日志级别
     * @var string
     */
    public string $level = '';

    public function __construct(string $message, string $module, string $level)
    {
        $this->level = $level;
        $this->message = $message;
        $this->module = $module;
    }
}