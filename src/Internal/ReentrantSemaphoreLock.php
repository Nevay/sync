<?php declare(strict_types=1);
namespace Nevay\Sync\Internal;

use Nevay\Sync\InterruptedException;
use Nevay\Sync\Lock;
use Revolt\EventLoop\FiberLocal;

/**
 * @internal
 */
final class ReentrantSemaphoreLock implements Lock {

    private readonly int $maxPermits;
    private readonly mixed $mode;
    /** @var FiberLocal<ReentrantCounter> */
    private readonly FiberLocal $reentrant;

    public function __construct(Semaphore $semaphore, int $maxPermits, mixed $mode) {
        $this->maxPermits = $maxPermits;
        $this->mode = $mode;
        $this->reentrant = new FiberLocal(static fn() => new ReentrantCounter($semaphore));
    }

    public function lock(): void {
        if (!$this->reentrant->get()->acquire($this->maxPermits, true, $this->mode)) {
            throw new InterruptedException();
        }
    }

    public function tryLock(): bool {
        return $this->reentrant->get()->acquire($this->maxPermits, false, $this->mode);
    }

    public function unlock(): void {
        $this->reentrant->get()->release();
    }
}
