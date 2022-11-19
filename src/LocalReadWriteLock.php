<?php declare(strict_types=1);
namespace Nevay\Sync;

use InvalidArgumentException;
use function sprintf;

final class LocalReadWriteLock implements ReadWriteLock {

    private readonly Lock $readLock;
    private readonly Lock $writeLock;

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
        $this->readLock = new Internal\ReentrantSemaphoreLock($semaphore, $maxReaders, 'read');
        $this->writeLock = new Internal\ReentrantSemaphoreLock($semaphore, $maxWriters, 'write');
    }

    public function readLock(): Lock {
        return $this->readLock;
    }

    public function writeLock(): Lock {
        return $this->writeLock;
    }
}
