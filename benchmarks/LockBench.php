<?php declare(strict_types=1);
namespace Nevay\Sync;

use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\Revs;
use PhpBench\Attributes\Warmup;

#[BeforeMethods('setup')]
final class LockBench {

    private Lock $lock;

    public function setup(): void {
        $this->lock = new LocalLock();
        $this->lock->lock();
        $this->lock->unlock();
    }

    #[Revs(100)]
    #[Warmup(10)]
    #[Iterations(10)]
    public function benchLockUnlock(): void {
        $this->lock->lock();
        $this->lock->unlock();
    }
}
