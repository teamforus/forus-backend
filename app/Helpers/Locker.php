<?php


namespace App\Helpers;


use Illuminate\Support\Facades\Cache;
use Psr\SimpleCache\InvalidArgumentException;

class Locker
{
    protected string $key;
    protected string $lockString;

    public mixed $lockValue = 1;
    public int $lockTime = 5;
    public int $maxWaitingTime = 30;

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
     * @return void
     * @throws InvalidArgumentException
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
     * @return bool
     * @throws InvalidArgumentException
     */
    public function waitForUnlockAndLock(): bool
    {
        if ($unlocked = $this->waitForUnlock()) {
            $this->lock();
        }

        return $unlocked;
    }
}
