<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Command;

use Swoole\Process;

class StorageLinkCommandService extends AbstractCommandService
{
    /**
     * @param Process|null $process
     * @return bool
     */
    public function handle(?Process $process): bool
    {
        $relative = $this->app->getOpt('relative');

        foreach ($this->links() as $link => $target) {
            if (file_exists($link)) {
                $this->error("The [$link] link already exists.");
                continue;
            }

            if ($relative) {
                app('files')->relativeLink($target, $link);
            } else {
                app('files')->link($target, $link);
            }

            $this->info("The [$link] link has been connected to [$target].");
        }

        $this->info('The links have been created.');

        return true;
    }

    protected function links()
    {
        return config('filesystems.links') ??
            [public_path('storage') => storage_path('app/public')];
    }

    /**
     * @return string
     */
    public function getCommand(): string
    {
        return 'storage:link';
    }

    /**
     * @return string
     */
    public function getCommandDescription(): string
    {
        return 'Create the symbolic links configured for the application.
                   <blue>{--relative : Create the symbolic link using relative paths.}</blue>';
    }
}