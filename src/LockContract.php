<?php

namespace Lysice\HyperfRedisLock;

interface LockContract {
    /**
     * Attempt to acquire the lock
     * @param callable|null $callback
     * @return mixed
     */
    public function get($callback = null);

    /**
     * Attempt to acquire the lock for the given number of seconds
     * @param $seconds
     * @param callable | null $callback
     * @return mixed
     */
    public function block($seconds, $callback = null);

    /**
     * Release the lock
     * @return mixed
     */
    public function release();

    /**
     * Returns the current owner of the lock
     * @return mixed
     */
    public function owner();

    /**
     * Releases this lock in disregard of ownership.
     * @return mixed
     */
    public function forceRelease();
}
