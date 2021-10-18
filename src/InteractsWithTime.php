<?php

namespace Lysice\HyperfRedisLock;

use Carbon\Carbon;
use DateTimeInterface;

trait InteractsWithTime
{
    /**
     * Get the number of seconds Until the given Datetime
     * @param $delay
     * @return int|mixed
     */
    protected function secondsUtil($delay)
    {
        $delay = $this->getDateTimeAfterInterval($delay);
        return $delay instanceof \DateTimeInterface
            ? max(0, $delay->getTimestamp() - $this->currentTime())
            : intval($delay);
    }

    /**
     * @param DateTimeInterface | int| \DateInterval $delay
     * @return int
     */
    protected function availableAt($delay = 0)
    {
        $delay = $this->getDateTimeAfterInterval($delay);
        return $delay instanceof DateTimeInterface
            ? $delay->getTimestamp()
            : Carbon::now()->addRealSeconds($delay)->getTimestamp();
    }

    /**
     * Get DateTime Instance from an interval
     * @param $delay
     * @return Carbon
     */
    protected function getDateTimeAfterInterval($delay)
    {
        if ($delay instanceof \DateInterval) {
            $delay = Carbon::now()->add($delay);
        }

        return $delay;
    }

    /**
     * Get current Time
     * @return int
     */
    protected function currentTime()
    {
        return Carbon::now()->getTimestamp();
    }
}
