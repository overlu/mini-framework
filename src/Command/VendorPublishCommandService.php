<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Command;

use League\Flysystem\Filesystem as Flysystem;
use League\Flysystem\FilesystemException;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\MountManager;
use Mini\Filesystem\Filesystem;
use Mini\Service\AbstractServiceProvider;
use Swoole\Process;

class VendorPublishCommandService extends AbstractCommandService
{
    /**
     * The filesystem instance.
     *
     * @var Filesystem
     */
    protected Filesystem $files;

    /**
     * The provider to publish.
     *
     * @var string|null
     */
    protected ?string $provider = null;

    /**
     * The tags to publish.
     *
     * @var array
     */
    protected array $tags = [];

    public function __construct()
    {
        parent::__construct();
        $this->files = app('files');
    }

    /**
     * @param Process|null $process
     * @return void
     */
    public function handle(?Process $process): void
    {
        $this->determineWhatShouldBePublished();
        $tags = empty($this->tags) ? [null] : $this->tags;
        foreach ($tags as $tag) {
            $this->publishTag($tag);
        }
        $this->info('Publishing complete.');
    }

    /**
     * Determine the provider or tag(s) to publish.
     *
     * @return void
     */
    protected function determineWhatShouldBePublished(): void
    {
        if ($this->getOpt('all')) {
            return;
        }

        [$this->provider, $this->tags] = [
            $this->getOpt('provider'), (array)$this->getOpt('tag'),
        ];
    }

    /**
     * Publishes the assets for a tag.
     *
     * @param string|null $tag
     * @return void
     * @throws FilesystemException
     */
    protected function publishTag(?string $tag): void
    {
        $published = false;

        $pathsToPublish = $this->getPathsToPublish($tag);

        foreach ($pathsToPublish as $from => $to) {
            $this->publishItem($from, $to);

            $published = true;
        }

        if ($published === false) {
            $this->error('Unable to locate publishable resources.');
        }
    }

    /**
     * Publish the given item from and to the given location.
     *
     * @param string $from
     * @param string $to
     * @return void
     * @throws FilesystemException
     */
    protected function publishItem(string $from, string $to): void
    {
        if ($this->files->isFile($from)) {
            $this->publishFile($from, $to);
            return;
        }

        if ($this->files->isDirectory($from)) {
            $this->publishDirectory($from, $to);
            return;
        }

        $this->error("Can't locate path: <{$from}>");
    }

    /**
     * Publish the directory to the given directory.
     *
     * @param string $from
     * @param string $to
     * @return void
     * @throws FilesystemException
     */
    protected function publishDirectory(string $from, string $to): void
    {
        $this->moveManagedFiles(new MountManager([
            'from' => new Flysystem(new LocalFilesystemAdapter($from)),
            'to' => new Flysystem(new LocalFilesystemAdapter($to)),
        ]));

        $this->status($from, $to, 'Directory');
    }

    /**
     * Move all the files in the given MountManager.
     *
     * @param MountManager $manager
     * @return void
     * @throws FilesystemException
     */
    protected function moveManagedFiles(MountManager $manager): void
    {
        foreach ($manager->listContents('from://', true) as $file) {
            if ($file['type'] === 'file' && (!$manager->fileExists('to://' . $file['path']) || $this->getOpt('force'))) {
                $manager->write('to://' . $file['path'], $manager->read('from://' . $file['path']));
            }
        }
    }

    /**
     * Write a status message to the console.
     *
     * @param string $from
     * @param string $to
     * @param string $type
     * @return void
     */
    protected function status(string $from, string $to, string $type): void
    {
        $from = str_replace(base_path(), '', realpath($from));

        $to = str_replace(base_path(), '', realpath($to));

        $this->message('<info>Copied ' . $type . '</info> <comment>[' . $from . ']</comment> <info>To</info> <comment>[' . $to . ']</comment>');
    }

    /**
     * Publish the file to the given path.
     *
     * @param string $from
     * @param string $to
     * @return void
     */
    protected function publishFile(string $from, string $to): void
    {
        if (!$this->files->exists($to) || $this->getOpt('force')) {
            $this->createParentDirectory(dirname($to));

            $this->files->copy($from, $to);

            $this->status($from, $to, 'File');
        }
    }

    /**
     * Create the directory to house the published files if needed.
     *
     * @param string $directory
     * @return void
     */
    protected function createParentDirectory(string $directory): void
    {
        if (!$this->files->isDirectory($directory)) {
            $this->files->makeDirectory($directory, 0755, true);
        }
    }

    /**
     * Get all of the paths to publish.
     *
     * @param string|null $tag
     * @return array
     */
    protected function getPathsToPublish(?string $tag): array
    {
        return AbstractServiceProvider::pathsToPublish(
            $this->provider, $tag
        );
    }

    public function getCommand(): string
    {
        return 'vendor:publish';
    }

    public function getCommandDescription(): string
    {
        return 'Publish any publishable assets from vendor packages.
                   <blue>{--force : Overwrite any existing files.}
                   {--all : Publish assets for all service providers without prompt.}
                   {--provider= : The service provider that has assets you want to publish.}</blue>';
    }
}