<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Filesystem\OSS;

use Carbon\Carbon;
use League\Flysystem\Config;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\FilesystemException;
use League\Flysystem\UnableToWriteFile;
use League\Flysystem\Visibility;
use Mini\Contracts\Container\BindingResolutionException;
use Mini\Filesystem\OSS\Traits\Signature;
use Mini\Filesystem\OSS\Traits\Verify;
use OSS\Core\OssException;
use OSS\OssClient;

/**
 * Class OSSAdapter
 * @package Mini\Filesystem\OSS
 */
class Adapter implements FilesystemAdapter
{
    use Verify, Signature;

    /**
     * @var OssClient
     */
    protected OssClient $client;

    /**
     * @var string
     */
    protected string $bucket;
    protected string $accessKeySecret;
    protected string $accessKeyId;
    protected string $endpoint;
    protected bool $isCName;
    protected bool $useSSL = false;

    public const SYSTEM_FIELD = [
        'bucket' => '${bucket}',
        'etag' => '${etag}',
        'filename' => '${object}',
        'size' => '${size}',
        'mimeType' => '${mimeType}',
        'height' => '${imageInfo.height}',
        'width' => '${imageInfo.width}',
        'format' => '${imageInfo.format}',
    ];
    /**
     * @var array|mixed
     */
    protected array $config;

    /**
     * @param array $config = [
     *     'access_id' => '',
     *     'access_secret' => '',
     *     'bucket' => '',
     *     'endpoint' => '',
     *     'timeout' => 3600,
     *     'connect_timeout' => 10,
     *     'is_cname' => false,
     *     'token' => '',
     *     'proxy' => null,
     * ]
     * @throws BindingResolutionException
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;
        $this->bucket = $this->config['bucket'];
        $this->accessKeyId = $this->config['access_id'];
        $this->accessKeySecret = $this->config['access_secret'];
        $this->endpoint = $this->config['endpoint'] ?? 'oss-cn-hangzhou.aliyuncs.com';
        $this->isCName = $this->config['is_cname'] ?? false;

        $this->client = make(OssClient::class, [
            $this->accessKeyId,
            $this->accessKeySecret,
            $this->endpoint,
            $this->isCName,
            $this->config['token'] ?? null,
            $this->config['proxy'] ?? null,
        ]);

        $this->client->setTimeout($this->config['timeout'] ?? 3600);
        $this->client->setConnectTimeout($this->config['connect_timeout'] ?? 10);
        $this->checkEndpoint();
    }

    /**
     * @param string $path
     * @return bool
     */
    public function fileExists(string $path): bool
    {
        return $this->client->doesObjectExist($this->bucket, $path);
    }

    /**
     * @param string $path
     * @param string $contents
     * @param Config $config
     */
    public function write(string $path, string $contents, Config $config): void
    {
        $this->client->putObject($this->bucket, $path, $contents, $this->getOssOptions($config));
    }

    /**
     * @param string $path
     * @param resource $contents
     * @param Config $config
     * @throws OssException
     */
    public function writeStream(string $path, $contents, Config $config): void
    {
        if (!is_resource($contents)) {
            throw UnableToWriteFile::atLocation($path, 'The contents is invalid resource.');
        }
        $i = 0;
        $bufferSize = 1024 * 1024;
        while (!feof($contents)) {
            if (false === $buffer = fread($contents, $bufferSize)) {
                throw UnableToWriteFile::atLocation($path, 'fread failed');
            }
            $position = $i * $bufferSize;
            $this->client->appendObject($this->bucket, $path, $buffer, $position, $this->getOssOptions($config));
            ++$i;
        }
        fclose($contents);
    }

    /**
     * @param string $path
     * @return string
     */
    public function read(string $path): string
    {
        return $this->client->getObject($this->bucket, $path);
    }

    /**
     * @param string $path
     * @return resource
     */
    public function readStream(string $path)
    {
        $resource = fopen('php://temp', 'rb+');
        fwrite($resource, $this->read($path));
        fseek($resource, 0);
        return $resource;
    }

    /**
     * @param string $path
     */
    public function delete(string $path): void
    {
        $this->client->deleteObject($this->bucket, $path);
    }

    /**
     * @param string $path
     * @throws FilesystemException
     */
    public function deleteDirectory(string $path): void
    {
        $lists = $this->listContents($path, true);
        if (!$lists) {
            return;
        }
        $objectList = [];
        foreach ($lists as $value) {
            $objectList[] = $value['path'];
        }
        $this->client->deleteObjects($this->bucket, $objectList);
    }

    /**
     * @param string $path
     * @param Config $config
     */
    public function createDirectory(string $path, Config $config): void
    {
        $this->client->createObjectDir($this->bucket, $path);
    }

    /**
     * @param string $path
     * @param string $visibility
     * @throws OssException
     */
    public function setVisibility(string $path, string $visibility): void
    {
        $this->client->putObjectAcl(
            $this->bucket,
            $path,
            ($visibility === Visibility::PUBLIC) ? 'public-read' : 'private'
        );
    }

    /**
     * @param string $path
     * @return FileAttributes
     * @throws OssException
     */
    public function visibility(string $path): FileAttributes
    {
        $response = $this->client->getObjectAcl($this->bucket, $path);
        return new FileAttributes($path, null, $response);
    }

    /**
     * @param string $path
     * @return FileAttributes
     */
    public function mimeType(string $path): FileAttributes
    {
        $response = $this->client->getObjectMeta($this->bucket, $path);
        return new FileAttributes($path, null, null, null, $response['content-type']);
    }

    /**
     * @param string $path
     * @return FileAttributes
     */
    public function lastModified(string $path): FileAttributes
    {
        $response = $this->client->getObjectMeta($this->bucket, $path);
        return new FileAttributes($path, null, null, strtotime($response['last-modified']));
    }

    /**
     * @param string $path
     * @return FileAttributes
     */
    public function fileSize(string $path): FileAttributes
    {
        $response = $this->client->getObjectMeta($this->bucket, $path);
        return new FileAttributes($path, (int)$response['content-length']);
    }

    /**
     * @param string $path
     * @param bool $deep
     * @return iterable
     * @throws OssException
     */
    public function listContents(string $path, bool $deep): iterable
    {
        $directory = rtrim($path, '\\/');

        $result = [];
        $nextMarker = '';
        while (true) {
            // max-keys 用于限定此次返回object的最大数，如果不设定，默认为100，max-keys取值不能大于1000。
            // prefix   限定返回的object key必须以prefix作为前缀。注意使用prefix查询时，返回的key中仍会包含prefix。
            // delimiter是一个用于对Object名字进行分组的字符。所有名字包含指定的前缀且第一次出现delimiter字符之间的object作为一组元素
            // marker   用户设定结果从marker之后按字母排序的第一个开始返回。
            $options = [
                'max-keys' => 1000,
                'prefix' => $directory ? $directory . '/' : $directory,
                'delimiter' => '/',
                'marker' => $nextMarker,
            ];
            $res = $this->client->listObjects($this->bucket, $options);

            // 得到nextMarker，从上一次$res读到的最后一个文件的下一个文件开始继续获取文件列表
            $nextMarker = $res->getNextMarker();
            $prefixList = $res->getPrefixList(); // 目录列表
            $objectList = $res->getObjectList(); // 文件列表
            if ($prefixList) {
                foreach ($prefixList as $value) {
                    $result[] = [
                        'type' => 'dir',
                        'path' => $value->getPrefix(),
                    ];
                    if ($deep) {
                        $result = array_merge($result, $this->listContents($value->getPrefix(), $deep));
                    }
                }
            }
            if ($objectList) {
                foreach ($objectList as $value) {
                    if (($value->getSize() === 0) && ($value->getKey() === $directory . '/')) {
                        continue;
                    }
                    $result[] = [
                        'type' => 'file',
                        'path' => $value->getKey(),
                        'timestamp' => strtotime($value->getLastModified()),
                        'size' => $value->getSize(),
                    ];
                }
            }
            if ($nextMarker === '') {
                break;
            }
        }

        return $result;
    }

    /**
     * @param string $source
     * @param string $destination
     * @param Config $config
     * @throws OssException
     */
    public function move(string $source, string $destination, Config $config): void
    {
        $this->client->copyObject($this->bucket, $source, $this->bucket, $destination);
        $this->client->deleteObject($this->bucket, $source);
    }

    /**
     * @param string $source
     * @param string $destination
     * @param Config $config
     * @throws OssException
     */
    public function copy(string $source, string $destination, Config $config): void
    {
        $this->client->copyObject($this->bucket, $source, $this->bucket, $destination);
    }

    /**
     * @param Config $config
     * @return array
     */
    private function getOssOptions(Config $config): array
    {
        $options = [];
        if ($headers = $config->get('headers')) {
            $options['headers'] = $headers;
        }

        if ($contentType = $config->get('Content-Type')) {
            $options['Content-Type'] = $contentType;
        }

        if ($contentMd5 = $config->get('Content-Md5')) {
            $options['Content-Md5'] = $contentMd5;
            $options['checkmd5'] = false;
        }
        return $options;
    }

    /**
     * @param $path
     * @return string
     */
    public function getUrl($path): string
    {
        if (!empty($this->config['url'])) {
            return rtrim($this->config['url'], '/') . '/' . ltrim($path, '/');
        }

        return $this->normalizeHost() . ltrim($path, '/');
    }


    /**
     * sign url.
     *
     * @param $path
     * @param $timeout
     * @param array $options
     * @param $method
     * @return bool|string
     */
    public function signUrl($path, $timeout, array $options = [], $method = OssClient::OSS_HTTP_GET): bool|string
    {
        return $this->client->signUrl($this->bucket, $path, $timeout, $method, $options);
    }

    /**
     * temporary file url.
     *
     * @param $path
     * @param $expiration
     * @param array $options
     * @param $method
     * @return bool|string
     */
    public function getTemporaryUrl($path, $expiration, array $options = [], $method = OssClient::OSS_HTTP_GET): bool|string
    {
        return $this->signUrl($path, Carbon::now()->diffInSeconds($expiration), $options, $method);
    }

    /**
     * @return string
     */
    protected function normalizeHost(): string
    {
        if ($this->isCName) {
            $domain = $this->endpoint;
        } else {
            $domain = $this->bucket . '.' . $this->endpoint;
        }

        if ($this->useSSL) {
            $domain = "https://{$domain}";
        } else {
            $domain = "http://{$domain}";
        }

        return rtrim($domain, '/') . '/';
    }

    /**
     * Check the endpoint to see if SSL can be used.
     */
    protected function checkEndpoint(): void
    {
        if (str_starts_with($this->endpoint, 'http://')) {
            $this->endpoint = substr($this->endpoint, strlen('http://'));
            $this->useSSL = false;
        } elseif (str_starts_with($this->endpoint, 'https://')) {
            $this->endpoint = substr($this->endpoint, strlen('https://'));
            $this->useSSL = true;
        }
    }

    /**
     * @return OssClient
     */
    public function getClient(): OssClient
    {
        return $this->client;
    }
}
