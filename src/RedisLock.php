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
        if ($this->seconds > 0) {
            $result = $this->redis->set($this->name, $this->owner,['nx', 'ex' => $this->seconds]);
        } else {
            $result = $this->redis->setnx($this->name, $this->owner);
            if(intval($result) === 1 && $this->seconds > 0) {
                $this->redis->expire($this->name, $this->seconds);
            }
        }

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
