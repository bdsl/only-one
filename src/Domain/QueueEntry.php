<?php

namespace Bdsl\OnlyOne\Domain;

/**
 * @psalm-immutable
 */
class QueueEntry
{
    public function __construct(public readonly string $id)
    {
    }

    public function equals(QueueEntry $that): bool
    {
        return $this->id === $that->id;
    }
}