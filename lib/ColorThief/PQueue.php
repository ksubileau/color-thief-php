<?php

namespace ColorThief;

/**
 * Simple priority queue.
 *
 * @phpstan-template T
 */
class PQueue
{
    /**
     * @var array
     * @phpstan-var array<T>
     */
    private $contents = [];

    /** @var bool */
    private $sorted = false;

    /**
     * @var callable
     * @phpstan-var callable(T, T): int
     */
    private $comparator = null;

    public function __construct(callable $comparator)
    {
        $this->setComparator($comparator);
    }

    private function sort(): void
    {
        usort($this->contents, $this->comparator);
        $this->sorted = true;
    }

    /**
     * @param mixed $object
     * @phpstan-param T $object
     */
    public function push($object): void
    {
        $this->contents[] = $object;
        $this->sorted = false;
    }

    /**
     * @return mixed
     * @phpstan-return T
     */
    public function peek(?int $index = null)
    {
        if (!$this->sorted) {
            $this->sort();
        }

        if (null === $index) {
            $index = $this->size() - 1;
        }

        return $this->contents[$index];
    }

    /**
     * @return mixed|null
     * @phpstan-return T|null
     */
    public function pop()
    {
        if (!$this->sorted) {
            $this->sort();
        }

        return array_pop($this->contents);
    }

    public function size(): int
    {
        return \count($this->contents);
    }

    /**
     * @phpstan-template R
     * @phpstan-param callable(T): R $function
     * @phpstan-return array<R>
     */
    public function map(callable $function): array
    {
        return array_map($function, $this->contents);
    }

    /**
     * @phpstan-param callable(T, T): int $function
     */
    public function setComparator(callable $function): void
    {
        $this->comparator = $function;
        $this->sorted = false;
    }

    /**
     * @return array<T>
     * @phpstan-return array<T>
     */
    public function debug()
    {
        if (!$this->sorted) {
            $this->sort();
        }

        return $this->contents;
    }
}
