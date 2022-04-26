<?php

namespace Lysice\HyperfRedisLock;

use Hyperf\Redis\RedisProxy;

/**
 * Class RedisLock
 * @package App\Utils\RedisLock
 */
class RedisLock extends Lock {
    /**
     * @var RedisProxy
     */
    protected $redis;

    public function __construct($redis, $name, $seconds, $owner = null)
    {
        parent::__construct($name, $seconds, $owner);
        $this->redis = $redis;
    }

    /**
     * @inheritDoc
     */
    public function acquire()
    {
        $result = $this->redis->setnx($this->name, $this->owner);

        if(intval($result) === 1 && $this->seconds > 0) {
            $this->redis->expire($this->name, $this->seconds);
        }

        return intval($result) === 1;
    }

    /**
     * @inheritDoc
     */
    protected function acquireShareLock(): bool
    {
        $shareLockScript = LockScripts::shareLock();
        $expireTime = $this->seconds > 0 ? $this->seconds : 30;
        $luaScript = sprintf($shareLockScript, $expireTime, $expireTime);
        $result = $this->redis->eval($luaScript, [$this->name, self::SHARE_LOCK_OWNER_KEY_PREFIX . $this->name, self::SHARE_LOCK_VALUE, $this->owner], 2);

        return intval($result) === 1;
    }

    /**
     * @inheritDoc
     */
    public function release()
    {
        if ($this->isOwnedByCurrentProcess()) {
            $this->redis->eval(LockScripts::releaseLock(), ['name' => $this->name, 'owner' => $this->owner],1);
        }
    }

    /**
     * @inheritDoc
     */
    protected function releaseShareLock()
    {
        $format = LockScripts::releaseShareLock();
        $this->redis->eval($format, [$this->name, self::SHARE_LOCK_OWNER_KEY_PREFIX . $this->name, $this->owner], 2);
    }

    /**
     * @inheritDoc
     */
    protected function getCurrentOwner()
    {
        return $this->redis->get($this->name);
    }

    /**
     * @inheritDoc
     */
    public function forceRelease()
    {
        $this->redis->del($this->name);
    }
}
