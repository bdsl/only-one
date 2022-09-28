<?php

namespace Bdsl\OnlyOne\Domain;

/**
 * @psalm-immutable
 */
class QueueEntry implements \JsonSerializable
{
    public function __construct(public readonly string $id)
    {
    }

    public function equals(QueueEntry $that): bool
    {
        return $this->id === $that->id;
    }

    public function jsonSerialize(): mixed
    {
        return ['id' => $this->id];
    }
}