<?php declare(strict_types=1);
namespace Nevay\Sync;

use Amp\Cancellation;
use Amp\CancelledException;
use Closure;
use Revolt\EventLoop;
use Throwable;
use function assert;

/**
 * Invokes the closure while holding the given lock.
 *
 * @template R
 * @param Lock $lock lock to hold while invoking the closure
 * @param Closure(...): R $closure closure to invoke
 * @param iterable $args arguments to provide to the closure
 * @param Cancellation|null $cancellation cancellation for lock acquisition
 * @return R result of the closure
 * @throws CancelledException if awaiting the lock is interrupted by the given
 *         cancellation
 * @throws InterruptedException if awaiting the lock is interrupted using
 *         {@link Suspension::resume()}
 * @throws Throwable if awaiting the lock is interrupted using
 *         {@link Suspension::throw()}
 * @throws Throwable if invoking the closure throws
 */
function synchronized(Lock $lock, Closure $closure, iterable $args = [], ?Cancellation $cancellation = null): mixed {
    $_lock = $lock;
    $_closure = $closure;
    $_args = $args;
    $_cancellation = $cancellation;
    unset($lock, $closure, $args, $cancellation);

    lockWithCancellation($_lock, $_cancellation);
    unset($_cancellation);

    try {
        /** @noinspection PhpUnusedLocalVariableInspection */
        return $_closure(...$_args, ...($_args = []));
    } finally {
        $_lock->unlock();
    }
}

/**
 * Acquires the lock.
 *
 * @param Lock $lock lock to acquire
 * @param Cancellation|null $cancellation cancellation for lock acquisition
 * @throws CancelledException if awaiting the lock is interrupted by the given
 *         cancellation
 * @throws InterruptedException if awaiting the lock is interrupted using
 *         {@link Suspension::resume()}
 * @throws Throwable if awaiting the lock is interrupted using
 *         {@link Suspension::throw()}
 */
function lockWithCancellation(Lock $lock, ?Cancellation $cancellation): void {
    $_lock = $lock;
    $_cancellation = $cancellation;
    unset($lock, $cancellation);

    if (!$_cancellation) {
        $_lock->lock();
    } elseif (!$_cancellation->isRequested()) {
        $suspension = EventLoop::getSuspension();
        $cancellationId = $_cancellation->subscribe(static function(CancelledException $e) use ($suspension): void {
            try {
                $suspension->throw($e);
            } catch (Throwable) {}
        });
        try {
            $_lock->lock();
        } catch (Throwable $e) {
            $_cancellation->unsubscribe($cancellationId);
            throw $e;
        }
        try {
            $_cancellation->unsubscribe($cancellationId);
        } catch (Throwable $e) {
            $_lock->unlock();
            throw $e;
        }
    } elseif (!$_lock->tryLock()) {
        $_cancellation->throwIfRequested();
        assert(false);
    }
}
