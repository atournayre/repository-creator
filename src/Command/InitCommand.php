<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Webmozart\Assert\Assert;

class InitCommand extends Command
{
    private readonly SymfonyStyle $io;
    private readonly Filesystem $filesystem;

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        parent::initialize($input, $output);
        $this->io = new SymfonyStyle($input, $output);
        $this->filesystem = new Filesystem();
    }

    private const DEFAULT_ISSUES_REPOSITORY = 'atournayre/create-repository-issues-template';
    private const DEFAULT_FILES_REPOSITORY = 'atournayre/create-repository-files-template';

    /**
     * @throws \Exception
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $configFilePath = $input->getArgument('path');
        Assert::endsWith($configFilePath, '.yaml', 'The configuration file must be a YAML file (.yaml).');

        $this->createConfigFile($configFilePath);
        $this->initializeIssuesFolder($input->getArgument('issuesRepository'));
        $this->initializeFilesFolder($input->getArgument('filesRepository'));

        return Command::SUCCESS;
    }

    protected function configure(): void
    {
        $this->setName('init')
            ->setDescription('Initializes the create repository command')
            ->setHelp('This command initializes the create repository command')
            ->addArgument('path', null, InputArgument::REQUIRED, 'Path to the configuration file')
            ->addArgument('issuesRepository', InputArgument::OPTIONAL, 'GitHub repository name for issues', self::DEFAULT_ISSUES_REPOSITORY)
            ->addArgument('filesRepository', InputArgument::OPTIONAL, 'GitHub repository name for files', self::DEFAULT_FILES_REPOSITORY)
        ;
    }

    private function createConfigFile(string $configFilePath): void
    {
        $this->filesystem->copy(__DIR__ . '/../../config/create_repository.yaml.dist', $configFilePath);

        if ($this->filesystem->exists($configFilePath)) {
            $this->io->success(sprintf('Configuration file created at %s', $configFilePath));
            return;
        }

        $this->io->error(sprintf('Configuration file could not be created at %s', $configFilePath));
    }

    private function initializeIssuesFolder(mixed $getArgument)
    {
        $this->filesystem->mkdir('issues');
        throw new \Exception('Not implemented yet');
    }

    private function initializeFilesFolder(mixed $getArgument)
    {
        $this->filesystem->mkdir('files');
        throw new \Exception('Not implemented yet');
    }
}
