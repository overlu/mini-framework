<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Command;


use Mini\Support\Command;

class StorageLinkCommandService extends BaseCommandService
{
    public static string $command = 'storage:link';

    public static string $description = "Create the symbolic links configured for the application. [ --relative : Create the symbolic link using relative paths ]";

    public function run()
    {
        $relative = $this->app->getOpt('relative');

        foreach ($this->links() as $link => $target) {
            if (file_exists($link)) {
                Command::error("The [$link] link already exists.");
                continue;
            }

            if ($relative) {
                app('files')->relativeLink($target, $link);
            } else {
                app('files')->link($target, $link);
            }

            Command::info("The [$link] link has been connected to [$target].");
        }

        Command::info('The links have been created.');
    }

    protected function links()
    {
        return config('filesystems.links') ??
            [public_path('storage') => storage_path('app/public')];
    }
}