<?php declare(strict_types=1);
namespace Nevay\Sync\Internal;

use Revolt\EventLoop\Suspension;
use Throwable;

/**
 * A counting semaphore.
 *
 * Supports acquiring permits for exclusive modes to support use cases like
 * MRSW locks.
 *
 * @internal
 */
interface Semaphore {

    /**
     * Acquires permits.
     *
     * @param int $maxPermits maximum number of permits to hold, permits will
     *        only be acquired once the count of currently acquired permits
     *        does not exceed $maxPermits-$permits
     * @param int $permits number of permits to acquire, has to be equal to or
     *        less than $maxPermits
     * @param bool $blocking whether this should block if the permits are not
     *        immediately available
     * @param mixed|null $mode mode to acquire, additional permits can only be
     *        acquired for the currently acquired mode
     * @return bool true if the permits were acquired, false otherwise
     * @throws Throwable if awaiting was cancelled externally using
     *         {@link Suspension::throw()}
     */
    public function acquire(int $maxPermits, int $permits = 1, bool $blocking = true, mixed $mode = null): bool;

    /**
     * Releases permits.
     *
     * @param int $permits number of permits to release, have to be acquired
     */
    public function release(int $permits = 1): void;

    /**
     * Returns the number of immediately available permits.
     *
     * @param int $maxPermits maximum number of available permits
     * @param mixed|null $mode mode of permits, permits are only available for
     *        the currently acquired mode
     * @return int number of immediately available permits
     */
    public function availablePermits(int $maxPermits, mixed $mode = null): int;

    /**
     * Returns the number of acquired permits.
     *
     * @return int number of acquired permits
     */
    public function acquiredPermits(): int;

    /**
     * Returns the number of suspensions that are waiting to acquire permits.
     *
     * @return int number of waiting suspensions
     */
    public function queueLength(): int;
}
