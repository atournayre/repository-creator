<?php

namespace App\Command;

use App\Configuration\Configuration;
use App\Creator\RepositoryCreator;
use App\DTO\Repository;
use Github\Exception\ApiLimitExceedException;
use Psr\Http\Client\ClientInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

class CreateRepositoryCommand extends Command
{
    private Configuration $configuration;

    public function __construct(
        private readonly ClientInterface $httplugClient,
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

        $clientName = $io->ask('What is your client name?', $this->configuration->defaultClientName);
        $projectName = $io->ask('What is your project name?', $this->configuration->defaultProjectName);
        $projetType = self::choiceQuestion($io, 'What is your project type?', $this->configuration->projectTypes, $this->configuration->defaultProjectType);
        $description = $io->ask('Describe your project:', $this->configuration->defaultDescription);
        $visibility = self::choiceQuestion($io, 'What is your project visibility?', $this->configuration->visibilities, $this->configuration->defaultVisibility);
//        $template = $this->>questionHelper->ask($input, $output, self::choiceQuestion('Enter the template you want to use', $this->configuration->getTemplates(), $this->configuration->getDefaultTemplate()));
        $template = null;
        $mainBranch = $io->ask('Define the main branch name', $this->configuration->mainBranch);
        $contributors = $this->askForContributors($io);

        $organization = null;

        $repository = Repository::create($clientName, $projectName, $projetType)
            ->withOrganization($organization)
            ->withDescription($description)
            ->withVisibility($visibility)
            ->withTemplate($template)
            ->withDefaultBranch($mainBranch)
            ->withFiles($this->configuration->files, $this->configuration->locale)
            ->withLabels($this->configuration->labels)
            ->withBranch($this->configuration->developBranch)
            ->withContributors($contributors)
            ->withMilstones($this->configuration->milestones)
        ;

        $output->writeln(['<info>⏳  One moment please, your repository is being created and configured...</info>', '']);
        $repositoryCreator = RepositoryCreator::instantiate($this->httplugClient, $this->configuration);
        try {
            $repositoryCreator->create($repository);

            $output->writeln([
                '<info>Congrats! Your repository is now ready.</info>',
                'Next: Let\'s make something amazing! 🎉',
            ]);
        } catch (ApiLimitExceedException $e) {
            $output->writeln('<error>API limit exceeded, please try again later</error>');
            $output->writeln($e->getMessage());
        } catch (\Exception $e) {
            $output->writeln([
                '<error>Something bad happened, we couldn\'t create the repo! 😓</error>',
                $e->getMessage(), $e->getFile(), $e->getLine(), $e->getCode(),
            ]);

            if ($keepOnFailure) {
                return Command::SUCCESS;
            }

            $repositoryCreator->delete($repository);
        }

        return Command::SUCCESS;
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
            ->setHelp('This command allows you to create a repository')
            ->addArgument('config', InputOption::VALUE_REQUIRED, 'The configuration file to use (.yaml).')
            ->addOption('keep-on-failure', null, InputOption::VALUE_OPTIONAL, 'Do not delete the repository if the creation fails', false)
        ;
    }

    private function askForContributors(SymfonyStyle $io): array
    {
        $contributors = $this->configuration->defaultContributors;
        $io->writeln(array_merge(
            [
                '<comment>According to the configuration, by default, the following contributors will be invited to the repository:</comment>',
            ],
            array_map(fn($contributor) => sprintf('<comment> * %s</comment>', $contributor), $contributors)
        ));
        while (true) {
            $contributor = $io
                ->ask('Invite another contributor ? Enter the contributor username (or press <return> to stop adding contributors)');
            if (empty($contributor)) {
                $io->writeln(array_merge(
                    [
                        '<comment>The following contributors will be invited to the repository:</comment>',
                    ],
                    array_map(fn($contributor) => sprintf('<comment> * %s</comment>', $contributor), $contributors),
                    [''],
                ));
                break;
            }
            $contributors[] = $contributor;
        }
        return $contributors;
    }
}
