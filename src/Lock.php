<?php

namespace Lysice\HyperfRedisLock;

use Hyperf\Utils\Str;
use Hyperf\Utils\InteractsWithTime;

abstract class Lock implements LockContract
{
    use InteractsWithTime;

    const LOCK_MODE_SHARE = 1;
    const LOCK_MODE_WRITE = 2;

    /**
     * The name of the lock
     * @var string
     */
    protected $name;

    /**
     * @var int
     */
    protected $seconds;

    /**
     * The scope identifier of this lock
     * @var string
     */
    protected $owner;

    public function __construct($name, $seconds, $owner = null)
    {
        if(is_null($owner)) {
            $owner = Str::random();
        }
        $this->name = $name;
        $this->seconds = $seconds;
        $this->owner = $owner;
    }

    /**
     * Attempt to acquire the lock
     * @return bool
     */
    abstract public function acquire();

    /**
     * Attempt to acquire the share lock
     * @return bool
     */
    abstract protected function acquireShareLock();

    /**
     * Attempt to acquire the write lock
     * @return bool
     */
    abstract protected function acquireWriteLock(): bool;

    /**
     * Release the lock
     * @return void
     */
    abstract public function release();

    /**
     * @return void
     */
    abstract protected function releaseShareLock();

    /**
     * @return void
     */
    abstract protected function releaseWriteLock();

    /**
     * Returns the owner value written into the driver for this lock
     * @return string
     */
    abstract protected function getCurrentOwner();

    /**
     * Attempt to acquire the lock
     * @param null|\Closure $callback
     * @param null|\Closure $finally
     * @return bool|mixed
     */
    public function get($callback = null, $finally = null)
    {
        $result = $this->acquire();
        if($result && is_callable($callback)) {
            try {
                return $callback();
            } finally {
                $this->release();
            }
        }
        if (!$result && is_callable($finally)) {
            return $finally();
        }

        return $result;
    }

    /**
     * @param $seconds
     * @param null $callback
     * @return bool|mixed
     * @throws LockTimeoutException
     */
    public function block($seconds, $callback = null)
    {
        $starting = $this->currentTime();
        while(! $this->acquire()) {
           usleep(250 * 1000);
           if($this->currentTime() - $seconds >= $starting) {
               throw new LockTimeoutException();
           }
        }

        if(is_callable($callback)) {
            try {
                return $callback();
            } finally {
                $this->release();
            }
        }

        return true;
    }

    /**
     * @throws LockTimeoutException
     */
    public function readLock($seconds, $callback = null, $interval = 250000)
    {
        $starting = $this->currentTime();
        while ($this->acquireShareLock() == 0) {
            usleep($interval);
            if ($this->currentTime() - $seconds >= $starting) {
                throw new LockTimeoutException();
            }
        }
        if (is_callable($callback)) {
            try {
                return $callback();
            } finally {
                $this->releaseShareLock();
            }
        }

        return true;
    }

    /**
     * @throws LockTimeoutException
     */
    public function writeLock($seconds, $callback = null, $interval = 250000)
    {
        $starting = $this->currentTime();
        while ($this->acquireWriteLock() == 0) {
            usleep($interval);
            if ($this->currentTime() - $seconds >= $starting) {
                throw new LockTimeoutException();
            }
        }
        if (is_callable($callback)) {
            try {
                return $callback();
            } finally {
                $this->releaseWriteLock();
            }
        }

        return true;
    }

    /**
     * Returns the current owner of the lock.
     *
     * @return string
     */
    public function owner()
    {
        return $this->owner;
    }

    /**
     * Determines whether this lock is allowed to release the lock in the driver.
     *
     * @return bool
     */
    protected function isOwnedByCurrentProcess()
    {
        return $this->getCurrentOwner() === $this->owner;
    }
}
