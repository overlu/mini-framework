<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Session\Drivers;

use Redis;
use SessionHandlerInterface;

class RedisSessionDriver implements SessionHandlerInterface
{
    /**
     * @var Redis
     */
    protected Redis $redis;

    /**
     * @var int
     */
    protected int $gcMaxLifeTime = 120;

    public function __construct()
    {
        $this->redis = redis(config('session.connection', 'session'));
        $this->gcMaxLifeTime = (int)config('session.lifetime', 120);
    }

    /**
     * Close the session.
     *
     * @see https://php.net/manual/en/sessionhandlerinterface.close.php
     * @return bool
     */
    public function close(): bool
    {
        return true;
    }

    /**
     * Destroy a session.
     *
     * @see https://php.net/manual/en/sessionhandlerinterface.destroy.php
     * @param string $id the session ID being destroyed
     * @return bool
     */
    public function destroy(string $id): bool
    {
        $this->redis->unlink($id);
        return true;
    }

    /**
     * Cleanup old sessions.
     *
     * @see https://php.net/manual/en/sessionhandlerinterface.gc.php
     * @param int $max_lifetime
     * @return bool
     */
    public function gc(int $max_lifetime): bool
    {
        return true;
    }

    /**
     * Initialize session.
     *
     * @see https://php.net/manual/en/sessionhandlerinterface.open.php
     * @param string $path the path where to store/retrieve the session
     * @param string $name the session name
     * @return bool
     */
    public function open(string $path, string $name): bool
    {
        return true;
    }

    /**
     * Read session data.
     *
     * @see https://php.net/manual/en/sessionhandlerinterface.read.php
     * @param string $id the session id to read data for
     * @return string
     */
    public function read(string $id): string
    {
        return $this->redis->get($id) ?: '';
    }

    /**
     * Write session data.
     *
     * @see https://php.net/manual/en/sessionhandlerinterface.write.php
     * @param string $id the session id
     * @param string $data
     * @return bool
     */
    public function write(string $id, string $data): bool
    {
        return (bool)$this->redis->setEx($id, (int)$this->gcMaxLifeTime * 60, $data);
    }
}
