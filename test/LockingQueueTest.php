<?php

use Bdsl\OnlyOne\Domain\LockingQueue;
use Bdsl\OnlyOne\Domain\QueueEntry;
use PHPUnit\Framework\TestCase;

class LockingQueueTest extends TestCase
{
    public function testItAddsLockToEmptyQueue(): void
    {
        $queue = LockingQueue::empty();

        $queue->enqueue(new QueueEntry('a'));

        $this->assertFalse($queue->isEmpty());
    }

    public function testSecondQueueItemWaitsForFirst(): void
    {
        $queue = LockingQueue::empty();

        $queue->enqueue(new QueueEntry('a'));
        $queue->enqueue(new QueueEntry('b'));

        $this->assertTrue($queue->hasSecondItem());
    }

    public function testThirdQueueItemKicksOutSecond(): void
    {
        $queue = LockingQueue::empty();

        $queue->enqueue(new QueueEntry('a'));
        $queue->enqueue(new QueueEntry('b'));
        $queue->enqueue(new QueueEntry('c'));

        $this->assertSame($queue->head()?->id, 'a');
        $this->assertSame($queue->tail()?->id, 'c');
    }

    public function testReleasingFirstItemMovesSecondToHead(): void
    {
        $queue = LockingQueue::empty();

        $queue->enqueue(new QueueEntry('a'));
        $queue->enqueue(new QueueEntry('b'));

        $queue->release(new QueueEntry('a'));

        $this->assertFalse($queue->hasSecondItem());
        $this->assertSame($queue->head()?->id, 'b');
    }

    public function testReleasingSecondItemLeavesHeadInPlace(): void
    {
        $queue = LockingQueue::empty();

        $queue->enqueue(new QueueEntry('a'));
        $queue->enqueue(new QueueEntry('b'));

        $queue->release(new QueueEntry('b'));

        $this->assertFalse($queue->hasSecondItem());
        $this->assertSame($queue->head()?->id, 'a');
    }

    public function testCannotQueueTwoItemsWithSameID(): void
    {
        $queue = LockingQueue::empty();

        $queue->enqueue(new QueueEntry('same'));

        $this->expectException(\Exception::class);
        $queue->enqueue(new QueueEntry('same'));
    }
}
