<?php

namespace Bdsl\OnlyOne;

use Bdsl\OnlyOne\Domain\LockingQueue;
use Bdsl\OnlyOne\Domain\QueueEntry;
use CzProject\GitPhp\Git;
use CzProject\GitPhp\GitException;
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

        $git = new Git();
        $temporaryDirectory = (new TemporaryDirectory())->create();

        $path = $temporaryDirectory->path('only-one');
        $git->cloneRepository($repositoryUrl, $path);

        $repo = $git->open($path); // will use later

        $path = $repo->getRepositoryPath();
        $queueFile = $path . "/$resourceName.json";

        $queueFileContent = @\file_get_contents($queueFile);
        if ($queueFileContent === false) {
            $queue = LockingQueue::empty();
        } else {
            // todo read queue from file
            throw new \Exception('not implemented');
        }

        $queueEntry = new QueueEntry(dechex(\random_int(1, 1_000_000_000)));
        $queue->enqueue($queueEntry);
        file_put_contents($queueFile, \json_encode($queue, \JSON_PRETTY_PRINT));

        $output->writeln("Cloned {$repositoryUrl} to $path");
        $repo->addAllChanges();
        try {
            $repo->commit("Add {$queueEntry->id} to queue for `$resourceName`");
        } catch (GitException $e) {
            $runnerResult = $e->getRunnerResult();
            if ($runnerResult !== null) {
                $output->writeln($runnerResult->getOutputAsString());
            }
            throw $e;
        }
        $repo->push();
        $output->writeln("Acquired lock on `$resourceName`, lock id {$queueEntry->id}");

        return 0;
    }
}