<?php

namespace Bdsl\OnlyOne\Domain;

class LockingQueue implements \JsonSerializable
{
    private ?QueueEntry $head = null;
    private ?QueueEntry $tail = null;

    private function __construct(?QueueEntry $head, ?QueueEntry $tail)
    {
        $this->head = $head;
        $this->tail = $tail;
    }

    public static function fromHeadAndTail(?QueueEntry $head, ?QueueEntry $tail): self
    {
        if ($tail && ! $head) {
            throw new \Exception('Cannot have tail without head');
        }

        return new self($head, $tail);
    }

    /**
     * @return array<string, ?QueueEntry>
     */
    public function jsonSerialize(): array
    {
        return [
                'head' => $this->head,
                'tail' => $this->tail,
        ];
    }

    public static function empty(): self
    {
        return new self(null, null);
    }

    public function enqueue(QueueEntry $queueEntry): void
    {
        if ($this->head?->equals($queueEntry)) {
            throw new \Exception("{$queueEntry->id} is already at the head of the queue");
        }

        if ($this->tail?->equals($queueEntry)) {
            throw new \Exception("{$queueEntry->id} is already queued of the queue");
        }

        /** @psalm-suppress RedundantCondition - I'm not sure why Psalm thinks that $this->head is always null. It isn't */
        if (is_null($this->head)) {
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