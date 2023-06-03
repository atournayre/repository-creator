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
    private const DEFAULT_ISSUES_REPOSITORY = 'atournayre/create-repository-issues-template';
    private const DEFAULT_FILES_REPOSITORY = 'atournayre/create-repository-files-template';

    private readonly SymfonyStyle $io;
    private readonly Filesystem $filesystem;

    /**
     * @throws \Exception
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $configFilePath = $input->getArgument('path');
        Assert::endsWith($configFilePath, '.yaml', 'The configuration file must be a YAML file (.yaml).');

        $this->createConfigFile($configFilePath);

        $currentPath = realpath(dirname($configFilePath));

        $this->initializeIssuesFolder($currentPath, $input->getArgument('issuesRepository'));
        $this->initializeFilesFolder($currentPath, $input->getArgument('filesRepository'));

        return Command::SUCCESS;
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

    private function initializeIssuesFolder(string $currentPath, string $repositoryName): void
    {
        $this->initializeFolder($currentPath, 'issues', $repositoryName);
    }

    private function initializeFolder(string $currentPath, string $folder, string $repositoryName): void
    {
        $zipName = sprintf('%s/%s-master.zip', $currentPath, $folder);
        $this->downloadZip($zipName, $repositoryName);
        $this->extractZip($zipName, $currentPath . \DIRECTORY_SEPARATOR);
        $this->filesystem->rename(
            explode(\DIRECTORY_SEPARATOR, $repositoryName)[1] . '-master',
            $currentPath . \DIRECTORY_SEPARATOR . $folder,
            true
        );
        $this->filesystem->remove($zipName);
    }

    private function downloadZip(string $zipName, string $repositoryName): void
    {
        $this->filesystem->copy($this->buildZipUrl($repositoryName), $zipName);
    }

    private function buildZipUrl(string $repositoryName): string
    {
        return sprintf('https://github.com/%s/archive/refs/heads/master.zip', $repositoryName);
    }

    /**
     * @param string $zipName
     * @param string $destination
     * @return void
     */
    protected function extractZip(string $zipName, string $destination): void
    {
        if (!extension_loaded('zip')) {
            throw new \RuntimeException('The zip extension must be loaded to use this command.');
        }

        if (!$this->filesystem->exists($zipName)) {
            throw new \RuntimeException(sprintf('The zip file %s does not exist.', $zipName));
        }

        $zipArchive = new \ZipArchive();
        $zipArchive->open($zipName);
        $zipArchive->extractTo($destination);
        $zipArchive->close();
    }

    private function initializeFilesFolder(string $currentPath, string $repositoryName): void
    {
        $this->initializeFolder($currentPath, 'files', $repositoryName);
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        parent::initialize($input, $output);
        $this->io = new SymfonyStyle($input, $output);
        $this->filesystem = new Filesystem();
    }

    protected function configure(): void
    {
        $this->setName('init')
            ->setDescription('Initializes the create repository command')
            ->setHelp('This command initializes the create repository command')
            ->addArgument('path', null, InputArgument::REQUIRED, 'Path to the configuration file')
            ->addArgument('issuesRepository', InputArgument::OPTIONAL, 'GitHub repository name for issues', self::DEFAULT_ISSUES_REPOSITORY)
            ->addArgument('filesRepository', InputArgument::OPTIONAL, 'GitHub repository name for files', self::DEFAULT_FILES_REPOSITORY);
    }
}
