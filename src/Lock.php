<?php

namespace Lysice\HyperfRedisLock;

use Hyperf\Utils\Str;

abstract class Lock implements LockContract
{
    use InteractsWithTime;

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
     * Release the lock
     * @return void
     */
    abstract public function release();

    /**
     * Returns the owner value written into the driver for this lock
     * @return string
     */
    abstract protected function getCurrentOwner();

    /**
     * Attempt to acquire the lock
     * @param null $callback
     * @return bool|mixed
     */
    public function get($callback = null)
    {
        $result = $this->acquire();
        if($result && is_callable($callback)) {
            try {
                return $callback();
            } finally {
                $this->release();
            }
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
           var_dump('try to get lock again...');
           var_dump([
               'seconds' => $seconds,
               'cur' => $this->currentTime(),
               'intval' => $this->currentTime() - $seconds
           ]);
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
