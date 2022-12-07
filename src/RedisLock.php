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
    public function release()
    {
        if ($this->isOwnedByCurrentProcess()) {
            $res = $this->redis->eval(LockScripts::releaseLock(), ['name' => $this->name, 'owner' => $this->owner],1);
            return $res == 1;
        }
        return false;
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
        $r = $this->redis->del($this->name);
        return $r == 1;
    }
}
