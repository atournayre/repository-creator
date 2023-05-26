<?php

namespace App\Command;

use App\Configuration\Configuration;
use App\Creator\RepositoryCreator;
use App\DTO\Repository;
use Github\Exception\ApiLimitExceedException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;

class CreateRepositoryCommand extends Command
{
    public function __construct(
        private readonly RepositoryCreator $repositoryCreator,
        private readonly Configuration     $config,
        string                             $name = null,
    )
    {
        parent::__construct($name);
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $helper = $this->getHelper('question');

        $clientName = $helper->ask($input, $output, self::question('What is your client name?', $this->config->defaultClientName));
        $projectName = $helper->ask($input, $output, self::question('What is your project name?', $this->config->defaultProjectName));
        $projetType = $helper->ask($input, $output, self::choiceQuestion('What is your project type?', $this->config->projectTypes));
        $description = $helper->ask($input, $output, self::question('Describe your project:', $this->config->defaultDescription));
        $visibility = $helper->ask($input, $output, self::choiceQuestion('What is your project visibility?', $this->config->visibilities, $this->config->defaultVisibility));
//        $template = $helper->ask($input, $output, self::choiceQuestion('Enter the template you want to use', $this->config->getTemplates(), $this->config->getDefaultTemplate()));
        $template = null;
        $mainBranch = $helper->ask($input, $output, self::question('Define the main branch name', $this->config->mainBranch));

        // atournayre is not an organization
        $organization = null;

        $repository = Repository::create($clientName, $projectName, $projetType)
            ->withOrganization($organization)
            ->withDescription($description)
            ->withVisibility($visibility)
            ->withTemplate($template)
            ->withDefaultBranch($mainBranch)
            ->withFiles($this->config->files)
            ->withLabels($this->config->labels)
            ->withBranch($this->config->developBranch)
            ->withContributors($this->config->defaultContributors)
        ;

        try {
            $this->repositoryCreator->create($repository);
        } catch (ApiLimitExceedException $e) {
            $output->writeln('<error>API limit exceeded, please try again later</error>');
            $output->writeln($e->getMessage());
        } catch (\Exception $e) {
            $output->writeln([$e->getMessage(), $e->getFile(), $e->getLine(), $e->getCode()]);
        }

        return Command::SUCCESS;
    }

    private static function question(string $question, string $default = null): Question
    {
        return new Question(
            sprintf("<info>%s</info> <comment>[%s]</comment> \n<info>> </info>", $question, $default),
            $default
        );
    }

    private static function choiceQuestion(string $question, array $choices, mixed $default = null): ChoiceQuestion
    {
        $choiceQuestion = sprintf('<info>%s</info>', $question);
        if (null !== $default) {
            $choiceQuestion = sprintf('<info>%s</info> <comment>[%s]</comment>', $question, $default);
        }

        return new ChoiceQuestion($choiceQuestion, $choices, $default);
    }

    protected function configure(): void
    {
        $this->setName('create-repository')
            ->setDescription('Create a repository');
    }
}
