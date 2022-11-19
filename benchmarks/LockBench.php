<?php declare(strict_types=1);
namespace Nevay\Sync;

use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\Revs;
use PhpBench\Attributes\Warmup;

final class LockBench {

    private Lock $lock;

    public function setupLock(): void {
        $this->lock = new LocalLock();
        $this->lock->lock();
        $this->lock->unlock();
    }

    public function setupSemaphore(): void {
        $this->lock = new LocalSemaphore(1);
        $this->lock->lock();
        $this->lock->unlock();
    }

    #[BeforeMethods('setupLock')]
    #[Revs(100)]
    #[Warmup(10)]
    #[Iterations(10)]
    public function benchLockLockUnlock(): void {
        $this->lock->lock();
        $this->lock->unlock();
    }

    #[BeforeMethods('setupSemaphore')]
    #[Revs(100)]
    #[Warmup(10)]
    #[Iterations(10)]
    public function benchSemaphoreLockUnlock(): void {
        $this->lock->lock();
        $this->lock->unlock();
    }
}
