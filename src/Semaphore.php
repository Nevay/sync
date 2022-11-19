<?php declare(strict_types=1);
namespace Nevay\Sync;

use Throwable;

/**
 * Counting semaphore.
 */
interface Semaphore extends Lock {

    /**
     * Acquires permits.
     *
     * Will block until the given number of permits becomes available.
     *
     * @param int $permits number of permits to acquire
     * @throws InterruptedException if awaiting the permits is interrupted using
     *         {@link Suspension::resume()}
     * @throws Throwable if awaiting the permits is interrupted using
     *         {@link Suspension::throw()}
     */
    public function lock(int $permits = 1): void;

    /**
     * Acquires permits if they are immediately available.
     *
     * @param int $permits number of permits to acquire
     * @return bool true if the permits were acquired, false otherwise
     */
    public function tryLock(int $permits = 1): bool;

    /**
     * Releases permits.
     *
     * @param int $permits number of permits to release
     */
    public function unlock(int $permits = 1): void;
}
