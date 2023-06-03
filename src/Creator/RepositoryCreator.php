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

class RepositoryCreator
{
    private readonly Client $client;
    private array $labels;
    private array $milestones;

    private function __construct(
        private readonly ClientInterface $httplugClient,
        private readonly Configuration   $configuration,
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
//        $repositoryCreator->protectBranches($repository);
        $repositoryCreator->addLabels($repository);
        $repositoryCreator->addContributors($repository);
        $repositoryCreator->addMilestones($repository);
        $repositoryCreator->enableAutomatedVulnerabilityAlerts($repository);
        $repositoryCreator->enableAutomatedSecurityFixes($repository);
        $repositoryCreator->addCodeOwners($repository);
        $repositoryCreator->addIssues($repository);
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
            $params = get_object_vars($label);
            $this->labels[$params['name']] = $this->client->issue()->labels()->create(
                $this->configuration->user,
                $repository->getName(),
                $params,
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
            $params = $milestone->toArray();
            $githubMilestone = $this->client->issues()->milestones()->create(
                $this->configuration->user,
                $repository->getName(),
                $params,
            );
            $this->milestones[$milestone->title] = $githubMilestone['number'];
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

    private function requiredStatusChecks(): ?array
    {
        $ciChecks = $this->configuration->getCiChecks();

        if ([] === $ciChecks) {
            return null;
        }

        return [
            'strict' => false,
            'contexts' => $ciChecks,
        ];
    }

    private function protectMainBranch(Repository $repository): void
    {
        $branch = $repository->defaultBranch;

        $this->client->repository()->protection()
            ->update(
                $this->configuration->user,
                $repository->getName(),
                $branch,
                [
                    'required_status_checks' => null,
                    'enforce_admins' => null,
                    'required_pull_request_reviews' => [
                        'dismiss_stale_reviews' => true,
                        'require_code_owner_reviews' => $repository->isPublic() || $repository->requireCodeOwnerReviews(),
                        'required_approving_review_count' => 1,
                    ],
                    'restrictions' => null,
                ]
            );
    }
    private function protectBranches(Repository $repository): void
    {
        $branches = [
            'feature',
            'fix',
            'hotfix',
            'release',
        ];

        foreach ($branches as $branch) {
            $this->client->getHttpClient()->post(
                sprintf('/repos/%s/%s/rulesets', $this->configuration->user, $repository->getName()),
                [
                    'name' => null,
                    'target' => 'branch',
                    'enforcement' => 'active',
                    'conditions' => [
                        'ref_name' => [
                            'include' => [
                                'refs/heads/'.$branch,
                            ]
                        ]
                    ],
                    'rules' => [
                        [
                            'type' => 'branch_name_pattern',
                            'parameters' => [
                                'pattern' => $branch,
                                'operator' => 'starts_with',
                            ],
                        ],
                        [
                            'type' => 'pull_request',
                            'parameters' => [
                                'dismiss_stale_reviews_on_push' => false,
                                'require_code_owner_review' => false,
                                'require_last_push_approval' => false,
                                'required_approving_review_count' => 1,
                                'required_review_thread_resolution' => false,
                            ],
                        ],
                    ],
                ],
            );
        }
    }

    private function addIssues(Repository $repository): void
    {
        foreach ($repository->issues as $issue) {
            $this->client->issues()->create(
                $this->configuration->user,
                $repository->getName(),
                array_merge(
                    $issue->toArray(),
                    [
                        'milestone' => $this->milestones[$issue->milestone],
                    ],
                )
            );
        }
    }
}
