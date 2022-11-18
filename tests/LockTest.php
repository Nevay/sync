<?php declare(strict_types=1);
namespace Nevay\Sync;

use Amp\PHPUnit\AsyncTestCase;
use InvalidArgumentException;
use LogicException;
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
}
