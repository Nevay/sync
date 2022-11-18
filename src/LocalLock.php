<?php declare(strict_types=1);
namespace Nevay\Sync;

use InvalidArgumentException;
use Nevay\Sync\Internal\ReentrantSemaphoreLock;
use function sprintf;

final class LocalLock implements Lock {

    private ReentrantSemaphoreLock $lock;

    /**
     * @param int $maxLocks number of concurrent locks, has to be positive
     */
    public function __construct(int $maxLocks = 1) {
        if ($maxLocks < 1) {
            throw new InvalidArgumentException(sprintf('Max locks (%d) has to be positive', $maxLocks));
        }

        $semaphore = new Internal\LocalSemaphore();
        $this->lock = new ReentrantSemaphoreLock($semaphore, $maxLocks, null);
    }

    public function lock(): void {
        $this->lock->lock();
    }

    public function tryLock(): bool {
        return $this->lock->tryLock();
    }

    public function unlock(): void {
        $this->lock->unlock();
    }
}
