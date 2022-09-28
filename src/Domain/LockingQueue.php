<?php

namespace Bdsl\OnlyOne\Domain;

class LockingQueue implements \JsonSerializable
{
    private ?QueueEntry $head = null;
    private ?QueueEntry $tail = null;

    private function __construct()
    {

    }

    public function jsonSerialize(): array
    {
        return [
                'head' => $this->head,
                'tail' => $this->tail,
        ];
    }

    public static function empty(): self
    {
        return new self();
    }

    public function enqueue(QueueEntry $queueEntry): void
    {
        if ($this->head?->equals($queueEntry)) {
            throw new \Exception("{$queueEntry->id} is already at the head of the queue");
        }

        if ($this->tail?->equals($queueEntry)) {
            throw new \Exception("{$queueEntry->id} is already queued of the queue");
        }

        if ($this->head === null) {
            $this->head = $queueEntry;
        } else {
            $this->tail = $queueEntry;
        }
    }

    public function isEmpty(): bool
    {
        return $this->head === null;
    }

    public function hasSecondItem(): bool
    {
        return $this->tail !== null;
    }

    public function head(): ?QueueEntry
    {
        return $this->head;
    }

    public function tail(): ?QueueEntry
    {
        return $this->tail;
    }

    public function release(QueueEntry $entry): void
    {
        if ($this->head?->equals($entry)) {
            $this->head = $this->tail;
            $this->tail = null;
            return;
        }

        if ($this->tail?->equals($entry)) {
            $this->tail = null;
        }
    }
}