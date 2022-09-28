<?php

namespace Bdsl\OnlyOne;

use CzProject\GitPhp\Git;
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
    protected function configure()
    {
        $this->setDefinition(new InputDefinition([
            new InputArgument("resource", InputArgument::REQUIRED, "The name of the resource on which to acquire a lock"),
            new InputArgument("repository", InputArgument::REQUIRED, "URI of the git repository to use as a distributed lock store"),
        ]));
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $resourceName = $input->getArgument('resource');
        $repositoryUrl = $input->getArgument("repository");

        $git = new Git();
        $temporaryDirectory = (new TemporaryDirectory())->create();

        $path = $temporaryDirectory->path('only-one');
        $git->cloneRepository($repositoryUrl, $path);

        $repo = $git->open($path);

        $output->writeln("Cloned {$repositoryUrl} to $path");

        return 0;
    }
}