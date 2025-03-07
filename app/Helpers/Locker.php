<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Cache;
use Psr\SimpleCache\InvalidArgumentException;

class Locker
{
    public mixed $lockValue = 1;
    public int $lockTime = 5;
    public int $maxWaitingTime = 30;
    protected string $key;
    protected string $lockString;

    /** @var int How often to check for unlock */
    protected int $sleepInterval = 1000 * 100;

    /**
     * @param string $key
     */
    public function __construct(string $key)
    {
        $this->lockString = "execution_lock.$key";
    }

    /**
     * @param string $key
     * @return Locker
     */
    public static function make(string $key): Locker
    {
        return new static($key);
    }

    /**
     * @throws InvalidArgumentException
     * @return void
     */
    public function lock(): void
    {
        Cache::set($this->lockString, $this->lockValue, $this->lockTime);
    }

    /**
     * @return void
     * @noinspection PhpUnused
     */
    public function unlock(): void
    {
        Cache::forget($this->lockString);
    }

    /**
     * @return bool
     */
    public function locked(): bool
    {
        return Cache::has($this->lockString);
    }

    /**
     * @return bool
     */
    public function waitForUnlock(): bool
    {
        $startTime = time();

        while ($this->locked() && (($startTime + $this->maxWaitingTime) > time())) {
            usleep($this->sleepInterval);
        }

        return !$this->locked();

    }

    /**
     * @throws InvalidArgumentException
     * @return bool
     */
    public function waitForUnlockAndLock(): bool
    {
        if ($unlocked = $this->waitForUnlock()) {
            $this->lock();
        }

        return $unlocked;
    }
}
