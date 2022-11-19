<?php declare(strict_types=1);
namespace Nevay\Sync;

use Revolt\EventLoop\Suspension;
use Throwable;

/**
 * Advisory lock.
 */
interface Lock {

    /**
     * Acquires the lock.
     *
     * Will block until the lock becomes available.
     *
     * @throws InterruptedException if awaiting the lock is interrupted using
     *         {@link Suspension::resume()}
     * @throws Throwable if awaiting the lock is interrupted using
     *         {@link Suspension::throw()}
     */
    public function lock(): void;

    /**
     * Acquires the lock if it is immediately available.
     *
     * @return bool true if the lock was acquired, false otherwise
     */
    public function tryLock(): bool;

    /**
     * Releases the lock.
     *
     * The lock must be released from the fiber that acquired it.
     */
    public function unlock(): void;
}
