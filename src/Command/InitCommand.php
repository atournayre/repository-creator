<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Webmozart\Assert\Assert;

class InitCommand extends Command
{
    private const DEFAULT_ISSUES_REPOSITORY = 'atournayre/create-repository-issues-template';
    private const DEFAULT_FILES_REPOSITORY = 'atournayre/create-repository-files-template';

    private readonly SymfonyStyle $io;
    private readonly Filesystem $filesystem;
    private string $currentPath;

    /**
     * @throws \Exception
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $configFilePath = $input->getArgument('path');
        Assert::endsWith($configFilePath, '.yaml', 'The configuration file must be a YAML file (.yaml).');

        try {
            $this->createGitHubTokenFile();
            $this->createConfigFile($configFilePath);
            $this->initializeIssuesFolder($input->getArgument('issuesRepository'));
            $this->initializeFilesFolder($input->getArgument('filesRepository'));

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->filesystem->remove($configFilePath);
            $this->io->error($e->getMessage());
            return Command::FAILURE;
        }
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

    private function createGitHubTokenFile(): void
    {
        $githubTokenFilePath = $this->currentPath . '/config/github_token';
        $githubTokenExists = $this->filesystem->exists($githubTokenFilePath);

        if ($githubTokenExists) {
            $this->io->success(sprintf('GitHub Token file already exists at %s', $githubTokenFilePath));
            return;
        }

        $question = new Question('Please enter your GitHub token: ');
        $question->setHidden(true);
        $githubToken = $this->io->askQuestion($question);

        $this->filesystem->remove($githubTokenFilePath);
        $this->filesystem->touch($githubTokenFilePath);
        $this->filesystem->appendToFile($githubTokenFilePath, $githubToken);
    }

    private function initializeIssuesFolder(string $repositoryName): void
    {
        $this->initializeFolder( 'issues', $repositoryName);
    }

    private function initializeFolder(string $folder, string $repositoryName): void
    {
        $zipName = sprintf('%s/%s-main.zip', $this->currentPath, $folder);
        $this->downloadZip($zipName, $repositoryName);
        $this->extractZip($zipName, $this->currentPath . \DIRECTORY_SEPARATOR);
        $this->filesystem->rename(
            explode(\DIRECTORY_SEPARATOR, $repositoryName)[1] . '-main',
            $this->currentPath . \DIRECTORY_SEPARATOR . $folder,
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
        return sprintf('https://github.com/%s/archive/refs/heads/main.zip', $repositoryName);
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

    private function initializeFilesFolder(string $repositoryName): void
    {
        $this->initializeFolder( 'templates', $repositoryName);
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        parent::initialize($input, $output);
        $this->io = new SymfonyStyle($input, $output);
        $this->filesystem = new Filesystem();

        $getcwd = getcwd();
        Assert::string($getcwd, 'Unable to get the current working directory.');
        $this->currentPath = $getcwd;
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
