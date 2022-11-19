<?php declare(strict_types=1);
namespace Nevay\Sync;

use InvalidArgumentException;
use LogicException;
use function sprintf;

final class LocalSemaphore implements Semaphore {

    private readonly Internal\Semaphore $semaphore;
    private readonly int $maxPermits;

    public function __construct(int $maxPermits) {
        if ($maxPermits < 1) {
            throw new InvalidArgumentException(sprintf('Max permits (%d) has to be positive', $maxPermits));
        }

        $this->semaphore = new Internal\LocalSemaphore();
        $this->maxPermits = $maxPermits;
    }

    public function lock(int $permits = 1): void {
        if ($permits < 1) {
            throw new InvalidArgumentException(sprintf('Permits (%d) has to be positive', $permits));
        }
        if ($permits > $this->maxPermits) {
            throw new InterruptedException();
        }

        if (!$this->semaphore->acquire($this->maxPermits, $permits, true)) {
            throw new InterruptedException();
        }
    }

    public function tryLock(int $permits = 1): bool {
        if ($permits < 1) {
            throw new InvalidArgumentException(sprintf('Permits (%d) has to be positive', $permits));
        }
        if ($permits > $this->maxPermits) {
            return false;
        }

        return $this->semaphore->acquire($this->maxPermits, $permits, false);
    }

    public function unlock(int $permits = 1): void {
        if ($permits < 1) {
            throw new InvalidArgumentException(sprintf('Permits (%d) has to be positive', $permits));
        }

        if ($permits > $this->semaphore->acquiredPermits()) {
            throw new LogicException(sprintf('Invalid call to Semaphore::unlock(), not enough permits acquired (acquired %d, trying to release %d)', $this->semaphore->acquiredPermits(), $permits));
        }

        $this->semaphore->release($permits);
    }
}
