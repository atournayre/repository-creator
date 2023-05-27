<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Webmozart\Assert\Assert;

class GenerateConfigCommand extends Command
{
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $configFilePath = $input->getArgument('path');
        Assert::endsWith($configFilePath, '.yaml', 'The configuration file must be a YAML file (.yaml).');

        $filesystem = new Filesystem();
        $filesystem->copy(
            __DIR__ . '/../../config/create_repository.yaml.dist',
            $configFilePath
        );

        if ($filesystem->exists($configFilePath)) {
            $io->success(sprintf('Configuration file created at %s', $configFilePath));
        } else {
            $io->error(sprintf('Configuration file could not be created at %s', $configFilePath));
        }

        return Command::SUCCESS;
    }

    protected function configure(): void
    {
        $this->setName('init')
            ->setDescription('Generate configuration file')
            ->setHelp('This command initializes the configuration file')
            ->addArgument('path', null, InputArgument::REQUIRED, 'Path to the configuration file')
        ;
    }
}
