<?php declare(strict_types=1);
namespace Nevay\Sync\Internal;

use LogicException;
use Throwable;

/**
 * @internal
 */
final class ReentrantCounter {

    public int $count = 0;

    private readonly Semaphore $semaphore;
    private ?Throwable $created;

    public function __construct(Semaphore $semaphore) {
        $this->semaphore = $semaphore;

        /** @noinspection PhpFieldImmediatelyRewrittenInspection */
        $this->created = null;
        assert($this->created = new LogicException('Call to Lock::lock() without call to Lock::unlock()'));
    }

    public function __destruct() {
        if (!$this->count) {
            return;
        }

        $this->semaphore->release();
        throw new LogicException('Missing call to Lock::unlock()', 0, $this->created);
    }
}
