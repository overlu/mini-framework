<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini;

use Composer\Script\Event;

/**
 * Class PackageScript
 * @package Mini
 */
class PackageScript
{
    /**
     * @param Event $event
     */
    public static function postRootPackageInstall(Event $event): void
    {
        file_exists('.env') || @copy('.env.example', '.env');
    }

    /**
     * @param Event $event
     */
    public static function postAutoloadDump(Event $event): void
    {
        file_exists('vendor/laravel') || @mkdir('vendor/laravel', 0644, true);
    }
}
