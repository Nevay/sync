<?php declare(strict_types=1);
namespace Nevay\Sync;

use Fiber;
use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\Revs;
use PhpBench\Attributes\Warmup;
use Revolt\EventLoop;

#[Warmup(10)]
#[Iterations(10)]
final class LockBench {

    private Lock $lock;
    private Fiber $lockFiber;

    public function setupLock(): void {
        $this->lock = new LocalLock();
    }

    public function setupSemaphore(): void {
        $this->lock = new LocalSemaphore(1);
    }

    public function setupFiber(): void {
        $this->lockFiber = new Fiber(function() {
            while ($suspension = Fiber::suspend()) {
                $this->lock->lock();
                $this->lock->unlock();
                $suspension->resume();
            }
        });
        $this->lockFiber->start();
    }

    #[BeforeMethods('setupLock')]
    #[Revs(100)]
    public function benchLockLockUnlock(): void {
        $this->lock->lock();
        $this->lock->unlock();
    }

    #[BeforeMethods('setupSemaphore')]
    #[Revs(100)]
    public function benchSemaphoreLockUnlock(): void {
        $this->lock->lock();
        $this->lock->unlock();
    }

    #[BeforeMethods(['setupLock', 'setupFiber'])]
    #[Revs(10)]
    public function benchLockBlockingLockUnlock(): void {
        $this->lock->lock();
        $this->lockFiber->resume($suspension = EventLoop::getSuspension());
        $this->lock->unlock();
        $suspension->suspend();
    }

    #[BeforeMethods(['setupSemaphore', 'setupFiber'])]
    #[Revs(10)]
    public function benchSemaphoreBlockingLockUnlock(): void {
        $this->lock->lock();
        $this->lockFiber->resume($suspension = EventLoop::getSuspension());
        $this->lock->unlock();
        $suspension->suspend();
    }
}
