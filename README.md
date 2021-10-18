# hyperf-redis-lock
English | [中文](./README-zh.md)

an easy redis-based distributed-lock implementation for hyperf 2.*。
This extension features distributed-lock includes block and unblock distributed-lock.

## Principle
The command in `Redis` is atomic.so we use `set` to make sure that your application executes sequentially.
The Redis version before 2.8 does't support the option `ex` in `set` command. so we just use `setnx+expire` instead.>> Version 1.*
The Redis version after 2.8 supports the option `ex` in `set` command. so we use `set nx ex`>> Version 2.*

so version 2.* supports the version of redis after 2.8.
version 1.* supports all the versions of redis.

## Redis version 
`redis-server --version`

## Install
Run `composer require lysice/hyperf-redis-lock`

## Usage
init the `redis` object
```
    /**
     * @var RedisProxy
     */
    protected $redis;

    public function __construct(RedisFactory $redisFactory)
    {
        $this->redis = $redisFactory->get('default');
    }
```

- non-block lock
we try to acquire the lock then return the result directly.If acquire the lock, execute the Closure and return the Closure's result. or return false; 
```
public function lock(ResponseInterface $response)
    {
        $lock = new RedisLock($this->redis, 'lock', 20);
        $res = $lock->get(function () {
            sleep(10);
            return [123];
        });
        return $response->json($res);
    }
```
- blocking lock
we try acquire the lock first.
1.if failed we try to acquire the lock every 250 ms util timeout(the time we waiting for is bigger than the expire time you set in your application)
2.if success then execute the closure and return the result of closure.  
Tips: if timeout occurs in the process of application you need to catch the exception `LockTimeoutException` to handle this situation.
for example:
```
/**
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function lockA(ResponseInterface $response)
    {
        try {
            $lock = new RedisLock($this->redis, 'lock', 4);
            $res = $lock->block(4, function () {
                return [456];
            });
            return $response->json(['res' => $res]);
        // catch the exception
        } catch (LockTimeoutException $exception) {
            var_dump('lockA lock check timeout');
            return $response->json(['res' => false, 'message' => 'timeout']);
        }
    }
```


## Finally

#### Contributing
Feel free to create a fork and submit a pull request if you would like to contribute.

#### Bug reports
Raise an issue on GitHub if you notice something broken.