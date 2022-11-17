<?php declare(strict_types=1);
namespace Nevay\Sync\Internal;

use Amp\PHPUnit\AsyncTestCase;
use Exception;
use Revolt\EventLoop;
use function Amp\async;

final class SemaphoreTest extends AsyncTestCase {

    public function testAcquireBelowMaxPermitsReturnsTrue(): void {
        $semaphore = new LocalSemaphore();

        $this->assertTrue($semaphore->acquire(2));
        $this->assertTrue($semaphore->acquire(2));
    }

    public function testAcquireExceedsMaxPermitsBlocksUntilAvailable(): void {
        $this->setMinimumRuntime(.01);

        $semaphore = new LocalSemaphore();
        $semaphore->acquire(1);
        EventLoop::delay(.01, fn() => $semaphore->release());

        $this->assertTrue($semaphore->acquire(1));
    }

    public function testAcquireExceedsMaxPermitsBlocksUntilAvailableMultiplePending(): void {
        $this->setMinimumRuntime(.03);

        $semaphore = new LocalSemaphore();
        for ($i = 0; $i < 3; $i++) {
            EventLoop::queue(function() use ($semaphore) {
                $semaphore->acquire(1);
                EventLoop::delay(.01, fn() => $semaphore->release());
            });
        }

        async($semaphore->acquire(...), 1)->await();
    }

    public function testAcquireExceedsMaxPermitsReturnsFalse(): void {
        $semaphore = new LocalSemaphore();

        $this->assertTrue($semaphore->acquire(1, blocking: false));
        $this->assertFalse($semaphore->acquire(1, blocking: false));
    }

    public function testAcquireCanBeCancelledExternally(): void {
        $this->setMinimumRuntime(.01);

        $semaphore = new LocalSemaphore();
        $semaphore->acquire(1);

        $suspension = EventLoop::getSuspension();
        $delay = EventLoop::delay(.01, fn() => $suspension->resume());
        try {
            $this->assertFalse($semaphore->acquire(1));
        } finally {
            EventLoop::cancel($delay);
        }
    }

    public function testAcquireCanBeCancelledExternallyWithException(): void {
        $this->setMinimumRuntime(.01);
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('cancelled');

        $semaphore = new LocalSemaphore();
        $semaphore->acquire(1);

        $suspension = EventLoop::getSuspension();
        $delay = EventLoop::delay(.01, fn() => $suspension->throw(new Exception('cancelled')));
        try {
            $this->assertFalse($semaphore->acquire(1));
        } finally {
            EventLoop::cancel($delay);
        }
    }

    public function testAcquireCanBeCancelledImmediately(): void {
        $semaphore = new LocalSemaphore();
        $semaphore->acquire(1);

        $suspension = EventLoop::getSuspension();
        EventLoop::queue($suspension->resume(...));
        EventLoop::queue($semaphore->release(...));
        $this->assertFalse($semaphore->acquire(1));
    }

    public function testQueueLength(): void {
        $semaphore = new LocalSemaphore();

        $this->assertSame(0, $semaphore->queueLength());

        $semaphore->acquire(1);
        $this->assertSame(0, $semaphore->queueLength());

        async($semaphore->acquire(...), 1);
        async($semaphore->acquire(...), 1);
        async(fn() => null)->await();

        $this->assertSame(2, $semaphore->queueLength());

        $semaphore->release();
        $semaphore->release();
        $this->assertSame(0, $semaphore->queueLength());
    }

    public function testAvailablePermits(): void {
        $semaphore = new LocalSemaphore();

        $this->assertSame(3, $semaphore->availablePermits(3));

        $semaphore->acquire(3);
        $this->assertSame(2, $semaphore->availablePermits(3));
    }
}
