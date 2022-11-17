<?php declare(strict_types=1);
namespace Nevay\Sync;

use Amp\Cancellation;
use InvalidArgumentException;
use function sprintf;

final class LocalReadWriteLock implements ReadWriteLock {

    private Lock $readLock;
    private Lock $writeLock;

    /**
     * @param int $maxReaders number of concurrent readers, has to be positive
     * @param int $maxWriters number of concurrent writers, has to be positive
     */
    public function __construct(int $maxReaders = PHP_INT_MAX, int $maxWriters = 1) {
        if ($maxReaders < 1) {
            throw new InvalidArgumentException(sprintf('Max readers (%d) has to be positive', $maxReaders));
        }
        if ($maxWriters < 1) {
            throw new InvalidArgumentException(sprintf('Max writers (%d) has to be positive', $maxWriters));
        }

        $semaphore = new Internal\LocalSemaphore();
        $this->readLock = self::createLock($semaphore, 'read', $maxReaders);
        $this->writeLock = self::createLock($semaphore, 'write', $maxWriters);
    }

    public function readLock(): Lock {
        return $this->readLock;
    }

    public function writeLock(): Lock {
        return $this->writeLock;
    }

    private static function createLock(Internal\Semaphore $semaphore, mixed $mode, int $maxPermits): Lock {
        return new class($semaphore, $mode, $maxPermits) implements Lock {

            public function __construct(
                private readonly Internal\Semaphore $semaphore,
                private readonly mixed $mode,
                private readonly int $maxPermits,
            ) {}

            public function lock(?Cancellation $cancellation = null): void {
                if (!$this->semaphore->acquire($this->maxPermits, mode: $this->mode)) {
                    throw new InterruptedException();
                }
            }

            public function tryLock(): bool {
                return $this->semaphore->acquire($this->maxPermits, blocking: false, mode: $this->mode);
            }

            public function unlock(): void {
                $this->semaphore->release();
            }
        };
    }
}
