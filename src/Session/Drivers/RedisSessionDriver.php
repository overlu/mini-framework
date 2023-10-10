<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Session\Drivers;

use SessionHandlerInterface;
use Swoole\Coroutine\Redis;

class RedisSessionDriver implements SessionHandlerInterface
{
    /**
     * @var \Redis|Redis
     */
    protected $redis;

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
     * @param string $session_id the session ID being destroyed
     * @return bool
     */
    public function destroy($session_id): bool
    {
        $this->redis->unlink($session_id);
        return true;
    }

    /**
     * Cleanup old sessions.
     *
     * @see https://php.net/manual/en/sessionhandlerinterface.gc.php
     * @param int $maxlifetime
     * @return bool
     */
    public function gc($maxlifetime): bool
    {
        return true;
    }

    /**
     * Initialize session.
     *
     * @see https://php.net/manual/en/sessionhandlerinterface.open.php
     * @param string $save_path the path where to store/retrieve the session
     * @param string $name the session name
     * @return bool
     */
    public function open($save_path, $name): bool
    {
        return true;
    }

    /**
     * Read session data.
     *
     * @see https://php.net/manual/en/sessionhandlerinterface.read.php
     * @param string $session_id the session id to read data for
     * @return string
     */
    public function read($session_id): string
    {
        return $this->redis->get($session_id) ?: '';
    }

    /**
     * Write session data.
     *
     * @see https://php.net/manual/en/sessionhandlerinterface.write.php
     * @param string $session_id the session id
     * @param string $session_data
     * @return bool
     */
    public function write($session_id, $session_data): bool
    {
        return (bool)$this->redis->setEx($session_id, (int)$this->gcMaxLifeTime * 60, $session_data);
    }
}
