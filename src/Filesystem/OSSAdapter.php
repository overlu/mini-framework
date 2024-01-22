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
     * @param FilesystemOperator $driver
     * @param Adapter $adapter
     * @param array $config
     */
    public function __construct(FilesystemOperator $driver, Adapter $adapter, array $config)
    {
        parent::__construct($driver, $adapter, $config);
    }
}
