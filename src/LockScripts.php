<?php

namespace Lysice\HyperfRedisLock;

class LockScripts {
    /**
     * release lock
     * @return string
     */
    public static function releaseLock()
    {
        return <<<'LUA'
if redis.call("get",KEYS[1]) == ARGV[1] then
    return redis.call("del",KEYS[1])
else
    return 0
end
LUA;
    }

    /**
     * lock in share mode
     * @return string
     */
    public static function shareLock()
    {
        return <<<LUA
local value = redis.call('get', KEYS[1])
if value == ARGV[1] then
    redis.call('expire', KEYS[1], %s)
    redis.call('set', KEYS[2], ARGV[2])
    return 1
elseif value == false then
    redis.call('set', KEYS[1], ARGV[1], 'ex', %s)
    redis.call('set', KEYS[2], ARGV[2])
    return 1
else
    return 0
end
LUA;
    }

    /**
     * release share lock
     * @return string
     */
    public static function releaseShareLock()
    {
        return <<<LUA
local value = redis.call('get', KEYS[2])
if value == ARGV[1] then
   return redis.call('del', KEYS[1], KEYS[2])
else
    return 0
end
LUA;
    }
}
