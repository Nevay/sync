<?php declare(strict_types=1);
namespace Nevay\Sync;

use Fiber;
use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\ParamProviders;
use PhpBench\Attributes\Revs;
use PhpBench\Attributes\Warmup;
use Revolt\EventLoop;
use Revolt\EventLoop\Suspension;

#[Warmup(10)]
#[Iterations(10)]
#[BeforeMethods('setup')]
#[ParamProviders('locks')]
final class LockBench {

    private Lock $lock;
    /** @var list<Fiber> */
    private array $fibers = [];
    private ?Suspension $suspension = null;

    public function setup(array $parameters): void {
        $this->lock = $parameters['lock'];
    }

    public function locks(): iterable {
        yield 'local lock' => ['lock' => new LocalLock()];
        yield 'local semaphore' => ['lock' => new LocalSemaphore(1)];
    }

    public function blockingProvider(): iterable {
        yield '1' => ['blocking' => 1];
        yield '10' => ['blocking' => 10];
        yield '100' => ['blocking' => 100];
        yield '1000' => ['blocking' => 1000];
    }

    public function setupFiber(array $parameters): void {
        $pending = 0;
        for ($n = $parameters['blocking'] ?? 0; --$n >= 0;) {
            $this->fibers[] = $fiber = new Fiber(function() use (&$pending) {
                for (;;) {
                    Fiber::suspend();
                    $pending++;
                    $this->lock->lock();
                    $this->lock->unlock();
                    if (!--$pending) {
                        $this->suspension?->resume();
                    }
                }
            });
            $fiber->start();
        }
    }

    #[Revs(100)]
    public function benchLockUnlock(): void {
        $this->lock->lock();
        $this->lock->unlock();
    }

    #[BeforeMethods('setupFiber')]
    #[ParamProviders('blockingProvider')]
    #[Revs(10)]
    public function benchBlockingLockUnlock(): void {
        $this->suspension = EventLoop::getSuspension();
        $this->lock->lock();
        foreach ($this->fibers as $fiber) {
            $fiber->resume();
        }
        $this->lock->unlock();
        $this->suspension->suspend();
    }
}
