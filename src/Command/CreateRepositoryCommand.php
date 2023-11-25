<?php

namespace App\Command;

use App\Configuration\Configuration;
use App\DTO\Repository;
use App\Service\Github\CreateGithubRepositoryService;
use App\Service\Github\DeleteGithubRepositoryService;
use Github\Exception\ApiLimitExceedException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

class CreateRepositoryCommand extends Command
{
    private array $successMessages = [
        '<info>Congrats! Your repository is now ready.</info>',
        'Next: Let\'s make something amazing! 🎉',
    ];
    private Configuration $configuration;

    public function __construct(
        private readonly CreateGithubRepositoryService $createGithubRepositoryService,
        private readonly DeleteGithubRepositoryService $deleteGithubRepositoryService,
        string                           $name = null,
    )
    {
        parent::__construct($name);
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('A new repository out-of-the-box!');
        $io->note(implode(' ', [
            'This command will create a new repository on GitHub.',
            'It will also add files, branches, labels and contributors.',
            'By default, it will delete the new repository if something went wrong.',
            'You can disable this behavior with the --keep-on-failure option.',
        ]));

        $this->configuration = Configuration::load($input->getArgument('config'));

        $keepOnFailure = is_null($input->getOption('keep-on-failure')) ?? false;

        if ($keepOnFailure) {
            $io->warning('The --keep-on-failure option is enabled. The new repository will not be deleted if something went wrong. Ctrl+C to abort.');
            $io->confirm('Do you want to continue?', true);
        }

        $clientName = $this->askOrSkip($input, 'clientName', fn () => $io->ask('What is your client name?', $this->configuration->defaultClientName));
        $projectName = $this->askOrSkip($input, 'projectName', fn() => $io->ask('What is your project name?', $this->configuration->defaultProjectName));
        $projetType = $this->askOrSkip($input, 'projectType', fn() => self::choiceQuestion($io, 'What is your project type?', $this->configuration->projectTypes, $this->configuration->defaultProjectType));
        $description = $io->ask('Describe your project', $this->configuration->defaultDescription, function (?string $answer): string {
            if (null === $answer || empty(trim($answer))) {
                throw new \RuntimeException('Description should not be empty');
            }

            return $answer;
        });
        $visibility = $this->askOrSkip($input, 'visibility', fn() => self::choiceQuestion($io, 'What is your project visibility?', $this->configuration->visibilities, $this->configuration->defaultVisibility));
//        $template = $this->>questionHelper->ask($input, $output, self::choiceQuestion('Enter the template you want to use', $this->configuration->getTemplates(), $this->configuration->getDefaultTemplate()));
        $template = null;
        $mainBranch = $this->askOrSkip($input, 'mainBranch', fn() => $io->ask('Define the main branch name', $this->configuration->mainBranch));
        $contributors = $this->askForContributors($io);
        $enableCodeOwners = $io->askQuestion(new ConfirmationQuestion('Do you want to enable auto-request review?', true));

        $organization = null;

        $repository = Repository::create(getcwd(), $clientName, $projectName, $projetType)
            ->withOrganization($organization)
            ->withDescription($description)
            ->withVisibility($visibility)
            ->withTemplate($template)
            ->withDefaultBranch($mainBranch)
            ->withFiles($this->configuration->files, $this->configuration->locale)
            ->withFolders($this->configuration->folders)
            ->withLabels($this->configuration->labels)
            ->withBranch($this->configuration->developBranch)
            ->withContributors($contributors)
            ->withMilstones($this->configuration->milestones)
            ->withIssues($this->configuration->issues, $this->configuration->locale)
        ;

        if ($enableCodeOwners) {
            $repository = $repository->withCodeOwners($this->configuration->codeowners);
            $this->addSuccessMessage([
                '',
                'Code owners are enabled',
                'Next: Check that the CODEOWNERS file is valid on '. $repository->getCodeOwnersUrl($this->configuration->user),
                'Then: Invite missing collaborators if needed! <comment>Manually fix any other issue.</comment>'
            ]);
        }

        $output->writeln(['<info>⏳  One moment please, your repository is being created and configured...</info>', '']);
        try {
            ($this->createGithubRepositoryService)($this->configuration, $repository);

            $output->writeln($this->successMessages);
        } catch (ApiLimitExceedException $e) {
            $this->outputError($io, 'API limit exceeded, please try again later', $e);
        } catch (\Exception|\Error $e) {
            $this->outputError($io, 'Something bad happened, we couldn\'t create the repo!', $e);

            if ($keepOnFailure) {
                return Command::SUCCESS;
            }

            $this->guardBeforeRepositoryDeletion($io);
            ($this->deleteGithubRepositoryService)($this->configuration, $repository);
        }

        return Command::SUCCESS;
    }

    private function guardBeforeRepositoryDeletion(SymfonyStyle $io): void
    {
        $io->note('Repository will be deleted in 10 seconds... Press Ctrl+C to abort and keep the repository as is.');
        $timeBeforeDeletion = 10;
        $progressBar = $io->createProgressBar($timeBeforeDeletion);
        $progressBar->start();
        for ($i = 0; $i < $timeBeforeDeletion; $i++) {
            $progressBar->advance();
            sleep(1);
        }
        $progressBar->finish();
    }

    private function outputError(SymfonyStyle $io, string $message, \Exception|\Error $e): void
    {
        $io->error($message);
        $io->writeln([
            sprintf('<fg=red>Message:</> %s', $e->getMessage()),
            sprintf('<fg=red>File:</> %s', $e->getFile()),
            sprintf('<fg=red>Line:</> %s', $e->getLine()),
            sprintf('<fg=red>Code:</> %s', $e->getCode()),
        ]);
    }

    private static function choiceQuestion(
        SymfonyStyle $io,
        string $question,
        array $choices,
        mixed $default = null
    ): string
    {
        return $io->askQuestion(new ChoiceQuestion($question, $choices, $default));
    }

    protected function configure(): void
    {
        $this->setName('create')
            ->setDescription('Create a repository')
            ->setHelp(file_get_contents(__DIR__.'/Help/CreateRepository.txt'))
            ->addArgument('config', InputOption::VALUE_REQUIRED, 'The configuration file to use (.yaml).')
            ->addArgument('clientName', InputArgument::OPTIONAL, 'The client name')
            ->addArgument('projectName', InputArgument::OPTIONAL, 'The project name')
            ->addArgument('projectType', InputArgument::OPTIONAL, 'The project type')
            ->addArgument('visibility', InputArgument::OPTIONAL, 'The visibility')
            ->addArgument('mainBranch', InputArgument::OPTIONAL, 'The main branch')
            ->addOption('keep-on-failure', null, InputOption::VALUE_OPTIONAL, 'Do not delete the repository if the creation fails', false)
        ;
    }

    private function askForContributors(SymfonyStyle $io): array
    {
        $displayContributorsList = function (SymfonyStyle $io, string $message, array $contributors): void {
            $messages = ['<comment>'.$message.'</comment>'];
            $messages = array_merge($messages, array_map(fn($contributor) => sprintf('<comment> * %s</comment>', $contributor), $contributors), ['']);
            $io->writeln($messages);
        };

        $contributors = $this->configuration->defaultContributors;

        $message = $contributors === []
            ? 'It looks like you\'re working alone on this project!'
            : 'According to the configuration, by default, the following contributors will be invited to the repository:';
        $displayContributorsList($io, $message, $contributors);

        while (true) {
            $contributor = $io
                ->ask('Invite another contributor ? Enter the contributor username (or press <return> to stop adding contributors)');
            if (empty($contributor)) {
                $message = $contributors === []
                    ? 'No contributors added!'
                    : 'The following contributors will be invited to the repository:';
                $displayContributorsList($io, $message, $contributors);
                break;
            }
            $contributors[] = $contributor;
        }
        return $contributors;
    }

    private function addSuccessMessage(array|string $message): void
    {
        if (is_array($message)) {
            $this->successMessages = array_merge($this->successMessages, $message);
            return;
        }
        $this->successMessages[] = $message;
    }

    private function askOrSkip(
        InputInterface $input,
        string $argumentName,
        callable $ask,
    ): string
    {
        return $input->getArgument($argumentName) ?? $ask();
    }
}
