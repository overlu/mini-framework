<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Filesystem;

use League\Flysystem\FilesystemOperator;
use Mini\Filesystem\OSS\Adapter;
use OSS\OssClient;

/**
 * Class AwsS3V3Adapter
 * @package Mini\Filesystem
 */
class OSSAdapter extends FilesystemAdapter
{

    /**
     * @param \League\Flysystem\FilesystemOperator $driver
     * @param array $config
     * @param OssClient $client
     * @return void
     */
    public function __construct(FilesystemOperator $driver, Adapter $adapter, array $config)
    {
        parent::__construct($driver, $adapter, $config);
    }
}
