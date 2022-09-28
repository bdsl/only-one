<?php

use Bdsl\OnlyOne\Domain\LockingQueue;
use Bdsl\OnlyOne\Domain\QueueEntry;
use PHPUnit\Framework\TestCase;

class LockingQueueTest extends TestCase
{
    public function testItAddsLockToEmptyQueue(): void
    {
        $queue = LockingQueue::empty();

        $queue->enqueue(new QueueEntry());

        $this->assertFalse($queue->isEmpty());
    }
}
