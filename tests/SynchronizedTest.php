<?php declare(strict_types=1);
namespace Nevay\Sync;

use Amp\CancelledException;
use Amp\PHPUnit\AsyncTestCase;
use Amp\TimeoutCancellation;
use function Amp\async;
use function Amp\delay;
use function Amp\Future\await;

final class SynchronizedTest extends AsyncTestCase {

    public function testSynchronizedSimpleLock(): void {
        $this->setMinimumRuntime(.03);
        $this->setTimeout(.04);
        $this->expectOutputString('l0Ll1Ll2L');

        $lock = new LocalLock();
        $futures = [];
        for ($i = 0; $i < 3; $i++) {
            $futures[] = async(synchronized(...), $lock, static function($i) {
                echo 'l', $i;
                delay(.01);
                echo 'L';
            }, [$i]);
        }

        await($futures);
    }

    public function testSynchronizedLockIncreasedMaxLocks(): void {
        $this->setMinimumRuntime(.02);
        $this->setTimeout(.03);
        $this->expectOutputString('l0l1Ll2LL');

        $lock = new LocalLock(2);
        $futures = [];
        for ($i = 0; $i < 3; $i++) {
            $futures[] = async(synchronized(...), $lock, static function($i) {
                echo 'l', $i;
                delay(.01);
                echo 'L';
            }, [$i]);
        }

        await($futures);
    }

    public function testSynchronizedReadWriteLock(): void {
        $this->setMinimumRuntime(.05);
        $this->setTimeout(.06);
        $this->expectOutputString('r0r1r2RRRw0Ww1Ww2Wr0r1r2RRR');

        $lock = new LocalReadWriteLock();
        $futures = [];
        for ($i = 0; $i < 3; $i++) {
            $futures[] = async(synchronized(...), $lock->readLock(), static function($i) {
                echo 'r', $i;
                delay(.01);
                echo 'R';
            }, [$i]);
        }
        for ($i = 0; $i < 3; $i++) {
            $futures[] = async(synchronized(...), $lock->writeLock(), static function($i) {
                echo 'w', $i;
                delay(.01);
                echo 'W';
            }, [$i]);
        }
        for ($i = 0; $i < 3; $i++) {
            $futures[] = async(synchronized(...), $lock->readLock(), static function($i) {
                echo 'r', $i;
                delay(.01);
                echo 'R';
            }, [$i]);
        }

        await($futures);
    }

    public function testSynchronizedCancellation(): void {
        $this->setMinimumRuntime(.01);
        $this->setTimeout(.02);
        $this->expectException(CancelledException::class);

        $lock = new LocalLock();
        $lock->lock();

        await([
            async(synchronized(...), $lock, fn() => $this->fail(), [], new TimeoutCancellation(.01)),
            async(delay(...), .01),
        ]);
    }
}
