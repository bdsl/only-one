<?php

namespace Bdsl\OnlyOne;

use Bdsl\OnlyOne\Domain\LockingQueue;
use Bdsl\OnlyOne\Domain\QueueEntry;
use CuyZ\Valinor\Mapper\Source\JsonSource;
use CuyZ\Valinor\Mapper\TreeMapper;
use CuyZ\Valinor\MapperBuilder;
use CzProject\GitPhp\Git;
use CzProject\GitPhp\GitException;
use CzProject\GitPhp\GitRepository;
use Spatie\TemporaryDirectory\TemporaryDirectory;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'start')]
class Start extends Command
{
    /**
     * @param GitRepository $repo
     * @param string $resourceName
     * @return string
     */
    public function getQueueFileName(GitRepository $repo, string $resourceName): string
    {
        $path = $repo->getRepositoryPath();
        $queueFile = $path . "/$resourceName.json";
        return $queueFile;
    }

    /**
     * @param string $queueFile
     * @param \CuyZ\Valinor\Mapper\TreeMapper $mapper
     * @return LockingQueue
     */
    public function getQueue(string $queueFile, \CuyZ\Valinor\Mapper\TreeMapper $mapper): LockingQueue
    {
        $queueFileContent = @\file_get_contents($queueFile);
        if ($queueFileContent === false) {
            $queue = LockingQueue::empty();
        } else {
            $queue = $mapper->map(LockingQueue::class, new JsonSource($queueFileContent));
        }
        return $queue;
    }

    protected function configure(): void
    {
        $this->setDefinition(new InputDefinition([
            new InputArgument("resource", InputArgument::REQUIRED, "The name of the resource on which to acquire a lock"),
            new InputArgument("repository", InputArgument::REQUIRED, "URI of the git repository to use as a distributed lock store"),
        ]));
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $resourceName = $input->getArgument('resource');
        \assert(is_string($resourceName));
        $repositoryUrl = $input->getArgument("repository");
        \assert(is_string($repositoryUrl));

        $mapper = (new MapperBuilder())
            ->registerConstructor(LockingQueue::fromHeadAndTail(...))
            ->mapper();

        $git = new Git();
        $temporaryDirectory = (new TemporaryDirectory())->create();

        $path = $temporaryDirectory->path('only-one');
        $git->cloneRepository($repositoryUrl, $path);

        $repo = $git->open($path); // will use later

        $queueFile = $this->getQueueFileName($repo, $resourceName);
        $queue = $this->getQueue($queueFile, $mapper);

        $queueEntry = new QueueEntry(dechex(\random_int(1, 1_000_000_000)));
        $queue->enqueue($queueEntry);
        file_put_contents($queueFile, \json_encode($queue, \JSON_PRETTY_PRINT));

        $output->writeln("Cloned {$repositoryUrl} to {$repo->getRepositoryPath()}");
        $repo->addAllChanges();
        try {
            $repo->commit("Add {$queueEntry->id} to queue for `$resourceName`", ['--author' => "Only One <only@one>"]);
        } catch (GitException $e) {
            $runnerResult = $e->getRunnerResult();
            if ($runnerResult !== null) {
                $output->writeln("Exception:");
                $output->writeln($runnerResult->getOutputAsString());
            }
            throw $e;
        }
        $repo->push(); // todo handle non-fast foward merge error by resetting local repo to remote and starting again.

        $queueHead = $queue->head();
        if ($queueHead?->equals($queueEntry)) {
            $output->writeln("Acquired lock on `$resourceName`, lock id {$queueEntry->id}");
        } else if ($queue->tail()?->equals($queueEntry)) {
            /** @psalm-suppress NullPropertyFetch (not sure why Psalm thinks queueHead is null here */
            $output->writeln("Lock on `$resourceName`, is currently held by lock id {$queueHead->id}, added {$queueEntry->id} to queue");
            $this->pollWaitingForHeadOfQueue($output, $queueEntry, $repo, $mapper, $resourceName);
        }

        return 0;
    }

    /**
     * Poll the git server until our queue entry is at the head, or throw on timeout or another queue entry taking our place at the tail*
     */
    private function pollWaitingForHeadOfQueue(OutputInterface $output, QueueEntry $queueEntry, GitRepository $repo, TreeMapper $mapper, string $resourceName): void
    {
        $maxTimeSeconds = 300;

        $startTimeStamp = microtime(true);

        $timeoutTimeStamp = $startTimeStamp + $maxTimeSeconds;

        while (microtime(true) < $timeoutTimeStamp) {
            $output->writeln("Waiting for lock to be released...");
            sleep(5);
            $repo->pull();

            $queueFile = $this->getQueueFileName($repo, $resourceName);
            $queue = $this->getQueue($queueFile, $mapper);

            if ($queue->head()?->equals($queueEntry)) {
                $output->writeln("Lock on $resourceName aquired");
                return;
            };

            if (! $queue->tail()?->equals($queueEntry)) {
                throw new \Exception("We were kicked out of queue for $resourceName");
            };
        }

        throw new \Exception("Timed out waiting for lock on $resourceName");
    }
}