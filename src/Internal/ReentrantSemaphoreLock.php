<?php declare(strict_types=1);
namespace Nevay\Sync\Internal;

use LogicException;
use Nevay\Sync\InterruptedException;
use Nevay\Sync\Lock;
use Revolt\EventLoop\FiberLocal;

/**
 * @internal
 */
final class ReentrantSemaphoreLock implements Lock {

    private Semaphore $semaphore;
    private int $maxPermits;
    private mixed $mode;
    /** @var FiberLocal<ReentrantCounter> */
    private FiberLocal $reentrant;

    public function __construct(Semaphore $semaphore, int $maxPermits, mixed $mode) {
        $this->semaphore = $semaphore;
        $this->maxPermits = $maxPermits;
        $this->mode = $mode;
        $this->reentrant = new FiberLocal(static fn() => new ReentrantCounter($semaphore));
    }

    public function lock(): void {
        if (!$this->doLock(true)) {
            throw new InterruptedException();
        }
    }

    public function tryLock(): bool {
        return $this->doLock(false);
    }

    private function doLock(bool $blocking): bool {
        $reentrant = $this->reentrant->get();
        if ($reentrant->count) {
            $reentrant->count++;
            return true;
        }
        if ($this->semaphore->acquire($this->maxPermits, 1, $blocking, $this->mode)) {
            $reentrant->count++;
            return true;
        }

        return false;
    }

    public function unlock(): void {
        $reentrant = $this->reentrant->get();
        if (!$reentrant->count) {
            throw new LogicException('Invalid call to Lock::unlock(), not holding lock');
        }

        if (!--$reentrant->count) {
            $this->semaphore->release();
        }
    }
}
