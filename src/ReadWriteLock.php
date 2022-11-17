<?php declare(strict_types=1);
namespace Nevay\Sync;

/**
 * Provides a pair of associated locks to synchronize read and write operations.
 */
interface ReadWriteLock {

    /**
     * Returns the lock for reading.
     *
     * @return Lock lock for reading
     */
    public function readLock(): Lock;

    /**
     * Returns the lock for writing.
     *
     * @return Lock lock for writing
     */
    public function writeLock(): Lock;
}
