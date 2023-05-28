<?php

namespace App\Creator;

use App\Configuration\Configuration;
use App\DTO\Codeowners;
use App\DTO\File;
use App\DTO\GitHubUrlParser;
use App\DTO\Repository;
use Github\AuthMethod;
use Github\Client;
use Psr\Http\Client\ClientInterface;

readonly class RepositoryCreator
{
    private Client $client;

    private function __construct(
        private ClientInterface $httplugClient,
        private Configuration   $configuration,
    )
    {
        $this->client = Client::createWithHttpClient($this->httplugClient);
        $this->client->authenticate(
            $this->configuration->githubToken,
            null,
            AuthMethod::ACCESS_TOKEN
        );
    }

    public static function instantiate(ClientInterface $httplugClient, Configuration $configuration): self
    {
        return new self($httplugClient, $configuration);
    }

    public function create(Repository $repository): void
    {
        $repositoryCreator = clone $this;
        $repositoryCreator->createRepository($repository);
        $repositoryCreator->addFiles($repository);
        $repositoryCreator->addBranches($repository);
        $repositoryCreator->protectMainBranch($repository);
        $repositoryCreator->addLabels($repository);
        $repositoryCreator->addContributors($repository);
        $repositoryCreator->addMilestones($repository);
        $repositoryCreator->enableAutomatedVulnerabilityAlerts($repository);
        $repositoryCreator->enableAutomatedSecurityFixes($repository);
        $repositoryCreator->addCodeOwners($repository);
    }

    private function doCreateRepository(Repository $repository): void
    {
        $this->client->repository()->create(
            $repository->getName(),
            $repository->description,
            null,
            $repository->visibility === Configuration::VISIBILITY_PUBLIC,
            null,
            true,
            null,
            null,
            null,
            true
        );
    }

    public function delete(Repository $repository): void
    {
        $this->client->repository()->remove(
            $this->configuration->user,
            $repository->getName()
        );
    }

    private function createRepository(Repository $repository): void
    {
        if ($repository->noTemplate()) {
            $this->doCreateRepository($repository);
            return;
        }
        $this->doCreateRepositoryWithTemplate($repository);
    }

    private function doCreateRepositoryWithTemplate(Repository $repository): void
    {
        $parameters = [
            'owner' => $this->configuration->user,
            'name' => $repository->getName(),
            'description' => $repository->description,
            'private' => $repository->visibility === Configuration::VISIBILITY_PRIVATE,
            'include_all_branches' => $this->configuration->includeAllBranchesFromTemplate($repository->template),
        ];

        $this->client->repository()->createFromTemplate(
            $repository->getTemplateOwner(),
            $repository->getTemplateRepo(),
            $parameters,
        );
    }

    private function addFiles(Repository $repository): void
    {
        $githubFiles = [];
        foreach ($repository->getGithubFiles() as $file) {
            $gitHubUrlParser = GitHubUrlParser::fromUrl($file->fullPath);
            $content = $this->client->repository()->contents()->download(
                $gitHubUrlParser->owner,
                $gitHubUrlParser->repository,
                $gitHubUrlParser->path
            );
            $githubFiles[$file->path] = File::create($file->path, $this->configuration->locale, $content);
        }

        $files = array_merge($repository->getFiles(), $githubFiles);

        foreach ($files as $file) {
            $this->client->repository()->contents()->create(
                $this->configuration->user,
                $repository->getName(),
                $file->path,
                $file->content,
                sprintf('Add %s file', $file->path),
                $repository->defaultBranch,
            );
        }
    }

    private function addBranches(Repository $repository): void
    {
        return;
        $branches = array_filter(
            $repository->branches,
            fn($branch) => $branch->name !== $repository->defaultBranch
        );

        $referenceData = ['ref' => 'refs/heads/featureA', 'sha' => '839e5185da9434753db47959bee16642bb4f2ce4'];
        $this->client->api('gitData')->references()->create(
            $this->configuration->user,
            $repository->getName(),
            $referenceData
        );

//        foreach ($branches as $branch) {
//            $branchName = sprintf('refs/heads/%s', $branch->name);
//            $this->client->git()->references()->create(
//                $this->configuration->user,
//                $repository->getName(),
//                [
//                    'ref' => $branchName,
//                    'sha' => sha1($branchName),
//                ]
//            );
//        }
    }

    private function addLabels(Repository $repository): void
    {
        foreach ($repository->labels as $label) {
            $this->client->issue()->labels()->create(
                $this->configuration->user,
                $repository->getName(),
                get_object_vars($label),
            );
        }
    }

    private function addContributors(Repository $repository): void
    {
        foreach ($repository->contributors as $contributor) {
            $this->client->repository()->collaborators()->add(
                $this->configuration->user,
                $repository->getName(),
                $contributor,
            );
        }
    }

    private function addMilestones(Repository $repository): void
    {
        foreach ($repository->milestones as $milestone) {
            $this->client->issues()->milestones()->create(
                $this->configuration->user,
                $repository->getName(),
                $milestone->toArray(),
            );
        }
    }

    public function enableAutomatedSecurityFixes(Repository $repository): void
    {
        $this->client->repository()->enableAutomatedSecurityFixes(
            $this->configuration->user,
            $repository->getName(),
        );
    }

    public function enableAutomatedVulnerabilityAlerts(Repository $repository): void
    {
        $this->client->repository()->enableVulnerabilityAlerts(
            $this->configuration->user,
            $repository->getName(),
        );
    }

    private function addCodeOwners(Repository $repository): void
    {
        $codeowners = Codeowners::fromArray($repository->codeOwners);
        $this->checkIfCodeownersFileCreationIsPossible($codeowners);

        $this->client->repository()->contents()->create(
            $this->configuration->user,
            $repository->getName(),
            '.github/CODEOWNERS',
            $codeowners->generateCodeowners(),
            'Add CODEOWNERS file',
            $repository->defaultBranch,
        );

        // TODO si le repo est public, ajouter une règle
        // Require a pull request before merging > Require review from Code Owners
//        $this->client->
    }

    private function checkIfCodeownersFileCreationIsPossible(Codeowners $codeowners): void
    {
        foreach ($codeowners->listReviewers() as $reviewer) {
            try {
                $this->client->user()->show($reviewer);
            } catch (\Exception $exception) {
                throw new \RuntimeException(
                    sprintf('Codeowner file not created because, user %s does not exist', $reviewer),
                    $exception->getCode(),
                    $exception
                );
            }
        }
    }

    private function protectMainBranch(Repository $repository): void
    {
        $branch = $repository->defaultBranch;

//        $this->client->repository()->protection()
//            ->updateStatusChecks(
//                $this->configuration->user,
//                $repository->getName(),
//                $branch,
//                [
//                    'strict' => true,
//                    'contexts' => [
//                        // TODO ajouter les checks de la CI qui doivent passer pour merger
////                        'continuous-integration/travis-ci',
//                    ],
//                ]
//            );

        if ($repository->isPrivate()) {
            return;
        }

        $this->client->repository()->protection()
            ->updatePullRequestReviewEnforcement(
                $this->configuration->user,
                $repository->getName(),
                $branch,
                [
                    'dismissal_restrictions' => [
                        'users' => [],
                        'teams' => [],
                    ],
                    'dismiss_stale_reviews' => true,
                    'require_code_owner_reviews' => $repository->requireCodeOwnerReviews(),
                    'required_approving_review_count' => 1,
                ]
            );
    }
}
