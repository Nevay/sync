<?php declare(strict_types=1);
namespace Nevay\Sync\Internal;

use Revolt\EventLoop;
use Revolt\EventLoop\Suspension;
use Throwable;
use function count;
use function key;
use function next;
use function spl_object_id;

/**
 * @internal
 */
final class LocalSemaphore implements Semaphore {

    private mixed $mode = null;
    private int $permits = 0;
    /** @var array<int, array{int, int, mixed, Suspension}> */
    private array $waitList = [];

    private function __clone(): never {
        assert(false);
    }

    public function acquire(int $maxPermits, int $permits = 1, bool $blocking = true, mixed $mode = null, bool $ignoreWaitList = false): bool {
        assert($permits > 0);
        assert($maxPermits >= $permits);

        if ($this->availablePermits($maxPermits, $mode, $ignoreWaitList) >= $permits) {
            $this->mode = $mode;
            $this->permits += $permits;
            return true;
        }
        if (!$blocking) {
            return false;
        }

        $suspension = EventLoop::getSuspension();
        $this->waitList[spl_object_id($suspension)] = [$maxPermits, $permits, $mode, $suspension];

        try {
            $suspension->suspend();
        } catch (Throwable $e) {}

        // check if cancelled/resumed externally
        if (isset($this->waitList[spl_object_id($suspension)])) {
            unset($this->waitList[spl_object_id($suspension)]);
            $this->processWaitList();

            return isset($e)
                ? throw $e
                : false;
        }
        assert(!isset($e));

        return true;
    }

    public function release(int $permits = 1): void {
        assert($permits > 0);
        assert($this->permits >= $permits);

        $this->permits -= $permits;
        $this->processWaitList();
    }

    public function availablePermits(int $maxPermits, mixed $mode = null, bool $ignoreWaitList = false): int {
        return !$this->permits || $this->mode === $mode && $this->permits < $maxPermits && ($ignoreWaitList || key($this->waitList) === null)
            ? $maxPermits - $this->permits
            : 0;
    }

    public function queueLength(): int {
        return count($this->waitList);
    }

    private function processWaitList(): void {
        while (($key = key($this->waitList)) !== null) {
            [$maxPermits, $permits, $mode, $suspension] = current($this->waitList);
            if (!$this->acquire($maxPermits, $permits, false, $mode, true)) {
                break;
            }

            next($this->waitList);
            try {
                $suspension->resume();
                unset($this->waitList[$key]);
            } catch (Throwable) {
                $this->permits -= $permits;
            }
        }
    }
}
