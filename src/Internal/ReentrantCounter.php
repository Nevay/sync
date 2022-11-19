<?php declare(strict_types=1);
namespace Nevay\Sync\Internal;

use LogicException;
use Throwable;

/**
 * @internal
 */
final class ReentrantCounter {

    private readonly Semaphore $semaphore;
    private int $count = 0;
    private ?Throwable $acquired = null;

    public function __construct(Semaphore $semaphore) {
        $this->semaphore = $semaphore;
    }

    public function acquire(int $maxPermits, bool $blocking, mixed $mode): bool {
        if ($this->count) {
            $this->count++;
            return true;
        }
        if ($this->semaphore->acquire($maxPermits, 1, $blocking, $mode)) {
            assert($this->acquired = new LogicException('Call to Lock::lock() without call to Lock::unlock()'));
            assert($this->count === 0);
            $this->count++;
            return true;
        }

        return false;
    }

    public function release(): void {
        if (!$this->count) {
            throw new LogicException('Invalid call to Lock::unlock(), not holding lock');
        }

        if (!--$this->count) {
            $this->semaphore->release();
        }
    }

    public function __destruct() {
        if (!$this->count) {
            return;
        }

        $this->semaphore->release();
        throw new LogicException('Missing call to Lock::unlock()', 0, $this->acquired);
    }
}
