# Synchronization primitives

## Installation

```shell
composer require tbachert/sync
```

## Usage

### Example

```php

$example = new Example();
await([
    async($example->increment(...), 5),
    async($example->increment(...), 4),
    async(fn() => var_dump($example->get())),
]);
var_dump($example->get());
// int(0)
// int(4)

$example = new ExampleSynchronized();
await([
    async($example->increment(...), 5),
    async($example->increment(...), 4),
    async(fn() => var_dump($example->get())),
]);
var_dump($example->get());
// int(9)
// int(9)


class Example {
    private int $value = 0;
    public function increment(int $count): void {
        $current = $this->value;
        delay(0);
        $this->value = $current + $count;
    }
    public function get(): int {
        return $this->value;
    }
}
class ExampleSynchronized {
    private Example $example;
    private ReadWriteLock $lock;
    public function __construct() {
        $this->example = new Example();
        $this->lock = new LocalReadWriteLock();
    }
    public function increment(int $count): void {
        synchronized(
            $this->lock->writeLock(),
            $this->example->increment(...), [$count],
        );
    }
    public function get(): int {
        return synchronized(
            $this->lock->readLock(),
            $this->example->get(...),
        );
    }
}
```
