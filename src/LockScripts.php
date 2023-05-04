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
     * eval param  key_name , share_mode, expire_time
     * @return string
     */
    public static function shareLock()
    {
        return <<<LUA
local mode = redis.call('hget', KEYS[1], 'mode')
if mode == false then
    local lock = redis.call('hset', KEYS[1], 'mode', ARGV[1])
    redis.call('expire', KEYS[1], ARGV[2])
    redis.call('hincrby', KEYS[1], 'lock_count', 1)
    return lock
elseif mode == ARGV[1] then
    redis.call('expire', KEYS[1], ARGV[2])
    redis.call('hincrby', KEYS[1], 'lock_count', 1)
    return 1
else
    return 0
end
LUA;
    }

    /**
     * lock in write mode
     * eval param key_name, LOCK_MODE_WRITE, LOCK_OWNER, EXPIRE_TIME
     * @return string
     */
    public static function writeLock()
    {
        return <<<LUA
local mode = redis.call('hget', KEYS[1], 'mode')
if mode == false then
    local res = redis.call('hset', KEYS[1], 'mode', ARGV[1])
    redis.call('expire', KEYS[1], ARGV[3])
    redis.call('hset', KEYS[1], 'owner', ARGV[2])
    return res
else
    return 0
end
LUA;
    }

    /**
     * release share lock
     * eval params key_name, LOCK_MODE_READ
     * @return string
     */
    public static function releaseShareLock()
    {
        return <<<LUA
local mode = redis.call('hget', KEYS[1], 'mode')
if mode == false then
    return 1
elseif mode ~= ARGV[1] then
    return 0
else
    local lock_count = redis.call('hget', KEYS[1], 'lock_count')
    if lock_count == false or tonumber(lock_count) <= 1 then
        return redis.call('del', KEYS[1])
    else
        redis.call('hincrby', KEYS[1], 'lock_count', -1)
        return 0
    end
end
LUA;
    }

    /**
     * release write lock
     * eval params key_name, LOCK_MODE_WRITE, LOCK_OWNER
     * @return string
     */
    public static function releaseWriteLock()
    {
        return <<<LUA
-- release writ lock: key_name, LOCK_MODE_WRITE LOCK_OWNER
local mode = redis.call('hget', KEYS[1], 'mode')
if mode == ARGV[1] then
    local owner = redis.call('hget', KEYS[1], 'owner')
    if owner == ARGV[2] then
        return redis.call('del', KEYS[1])
    else
        return 0
    end
else
    return 0
end
LUA;

    }
}