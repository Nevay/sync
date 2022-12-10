<?php declare(strict_types=1);
namespace Nevay\Sync\Adapter;

use Amp\Sync\AbstractMutexTest;
use Amp\Sync\Lock;
use Amp\Sync\Mutex;
use Error;
use InvalidArgumentException;
use Nevay\Sync\LocalSemaphore;

final class AmpMutexTest extends AbstractMutexTest {

    public function createMutex(): Mutex {
        try {
            $lock = new LocalSemaphore(1);
        } catch (InvalidArgumentException $e) {
            throw new Error(previous: $e);
        }

        return new class($lock) implements Mutex {

            public function __construct(private readonly \Nevay\Sync\Lock $lock) {}

            public function acquire(): Lock {
                $this->lock->lock();
                return new Lock($this->lock->unlock(...));
            }
        };
    }
}
