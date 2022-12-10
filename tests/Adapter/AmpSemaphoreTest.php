<?php declare(strict_types=1);
namespace Nevay\Sync\Adapter;

use Amp\Sync\AbstractSemaphoreTest;
use Amp\Sync\Lock;
use Amp\Sync\Semaphore;
use Error;
use InvalidArgumentException;
use Nevay\Sync\LocalSemaphore;

final class AmpSemaphoreTest extends AbstractSemaphoreTest {

    public function createSemaphore(int $locks): Semaphore {
        try {
            $lock = new LocalSemaphore($locks);
        } catch (InvalidArgumentException $e) {
            throw new Error(previous: $e);
        }

        return new class($lock) implements Semaphore {

            public function __construct(private readonly \Nevay\Sync\Lock $lock) {}

            public function acquire(): Lock {
                $this->lock->lock();
                return new Lock($this->lock->unlock(...));
            }
        };
    }
}
