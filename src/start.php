<?php

namespace Bdsl\OnlyOne;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'start')]
class Start extends Command
{
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Testing, testing');
        $output->writeln("1, 2, 3");

        return 0;
    }
}