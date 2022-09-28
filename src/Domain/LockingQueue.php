<?php

namespace Bdsl\OnlyOne\Domain;

class LockingQueue
{
    private ?QueueEntry $head = null;

    private function __construct()
    {

    }

    public static function empty(): self
    {
        return new self();
    }

    public function enqueue(QueueEntry $queueEntry): void
    {
        $this->head = $queueEntry;
    }

    public function isEmpty(): bool
    {
        return $this->head === null;
    }
}