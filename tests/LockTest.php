<?php declare(strict_types=1);
namespace Nevay\Sync;

use Amp\PHPUnit\AsyncTestCase;
use InvalidArgumentException;
use LogicException;
use Revolt\EventLoop;
use function Amp\async;
use function Amp\delay;
use function Amp\Future\await;

final class LockTest extends AsyncTestCase {

    public function testNotCallingUnlockOnLockedLockThrows(): void {
        $this->expectException(LogicException::class);

        $lock = new LocalLock();
        $lock->lock();
    }

    public function testCallingUnlockOnNotLockedLockThrows(): void {
        $this->expectException(LogicException::class);

        $lock = new LocalLock();
        $lock->unlock();
    }

    public function testReentrantSameFiber(): void {
        $lock = new LocalLock();
        $lock->lock();
        try {
            $this->assertTrue($lock->tryLock());
            $lock->unlock();
        } finally {
            $lock->unlock();
        }
    }

    public function testReentrantDifferentFiber(): void {
        $lock = new LocalLock();
        $lock->lock();
        try {
            $this->assertFalse(async($lock->tryLock(...))->await());
        } finally {
            $lock->unlock();
        }
    }

    public function testReentrantDoesNotCountTowardsLockLimit(): void {
        $this->setMinimumRuntime(.02);
        $this->setTimeout(.03);

        $lock = new LocalLock(2);
        $futures = [];
        for ($i = 0; $i < 4; $i++) {
            $futures[] = async(function() use ($lock) {
                $lock->lock();
                try {
                    $lock->lock();
                    delay(.01);
                    $lock->unlock();
                } finally {
                    $lock->unlock();
                }
            });
        }

        await($futures);
    }

    public function testSemaphoreLockPermitsExceedsMaxPermitsThrows(): void {
        $this->expectException(InterruptedException::class);

        $lock = new LocalSemaphore(1);
        $lock->lock(2);
    }

    public function testSemaphoreTryLockPermitsExceedsMaxPermitsReturnsFalse(): void {
        $lock = new LocalSemaphore(1);

        $this->assertFalse($lock->tryLock(2));
    }

    public function testSemaphoreCanBeReleasedFromDifferentFiber(): void {
        $this->setMinimumRuntime(.01);
        $this->setTimeout(.02);
        $lock = new LocalSemaphore(1);
        EventLoop::delay(.01, fn() => $lock->unlock());
        $lock->lock();
        $lock->lock();
    }

    public function testLockNonPositiveMaxLocksThrows(): void {
        $this->expectException(InvalidArgumentException::class);

        new LocalLock(0);
    }

    public function testReadWriteLockNonPositiveMaxReadersThrows(): void {
        $this->expectException(InvalidArgumentException::class);

        new LocalReadWriteLock(maxReaders: 0);
    }

    public function testReadWriteLockNonPositiveMaxWritersThrows(): void {
        $this->expectException(InvalidArgumentException::class);

        new LocalReadWriteLock(maxWriters: 0);
    }

    public function testSemaphoreNonPositiveMaxPermitsThrows(): void {
        $this->expectException(InvalidArgumentException::class);

        new LocalSemaphore(0);
    }

    public function testSemaphoreNonPositivePermitsLockThrows(): void {
        $this->expectException(InvalidArgumentException::class);

        $lock = new LocalSemaphore(1);
        $lock->lock(0);
    }

    public function testSemaphoreNonPositivePermitsTryLockThrows(): void {
        $this->expectException(InvalidArgumentException::class);

        $lock = new LocalSemaphore(1);
        $lock->tryLock(0);
    }

    public function testSemaphoreNonPositivePermitsUnlockThrows(): void {
        $this->expectException(InvalidArgumentException::class);

        $lock = new LocalSemaphore(1);
        $lock->unlock(0);
    }
}
