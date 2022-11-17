<?php declare(strict_types=1);
namespace Nevay\Sync;

use InvalidArgumentException;
use function sprintf;

final class LocalLock implements Lock {

    private readonly Internal\Semaphore $semaphore;
    private readonly int $maxPermits;

    /**
     * @param int $maxLocks number of concurrent locks, has to be positive
     */
    public function __construct(int $maxLocks = 1) {
        if ($maxLocks < 1) {
            throw new InvalidArgumentException(sprintf('Max locks (%d) has to be positive', $maxLocks));
        }

        $this->semaphore = new Internal\LocalSemaphore();
        $this->maxPermits = $maxLocks;
    }

    public function lock(): void {
        if (!$this->semaphore->acquire($this->maxPermits)) {
            throw new InterruptedException();
        }
    }

    public function tryLock(): bool {
        return $this->semaphore->acquire($this->maxPermits, blocking: false);
    }

    public function unlock(): void {
        $this->semaphore->release();
    }
}
