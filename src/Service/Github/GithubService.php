<?php

namespace App\Service\Github;

use App\DTO\Codeowners;
use App\DTO\File;
use App\DTO\GitHubUrlParser;
use App\DTO\Issue;
use App\DTO\Label;
use App\DTO\Milestone;
use App\Http\GithubClient;
use Github\Client;

readonly class GithubService
{
    public function __construct(
        private GithubClient $client
    )
    {
    }

    // Get client
    public function getClient(): Client
    {
        return $this->client->getClient();
    }

    public function createLabel(string $userName, string $repositoryName, Label $label): void
    {
        $this->getClient()->issue()->labels()->create($userName, $repositoryName, get_object_vars($label));
    }

    /**
     * @param string $userName
     * @param string $repositoryName
     * @param array|Label[] $labels
     * @return void
     */
    public function createLabels(string $userName, string $repositoryName, array $labels): void
    {
        foreach ($labels as $label) {
            $this->createLabel($userName, $repositoryName, $label);
        }
    }

    public function createRepository(
        string $repositoryName,
        string $description,
        bool $public = false,
    ): void
    {
        $this->getClient()->repository()->create(
            $repositoryName,
            $description,
            null,
            $public,
            null,
            true,
            null,
            null,
            null,
            true
        );
    }

    public function deleteRepository(string $userName, string $repositoryName): void
    {
        $this->getClient()->repository()->remove($userName, $repositoryName);
    }

    public function createRepositoryFromTemplate(
        string $templateOwner,
        string $templateRepo,
        string $userName,
        string $repositoryName,
        string $description,
        bool $private = false,
        bool $includeAllBranches = false,
    ): void
    {
        $this->getClient()->repository()->createFromTemplate(
            $templateOwner,
            $templateRepo,
            [
                'owner' => $userName,
                'name' => $repositoryName,
                'description' => $description,
                'private' => $private,
                'include_all_branches' => $includeAllBranches,
            ],
        );
    }

    public function createFile(
        string $userName,
        string $repositoryName,
        string $branch,
        File $file,
    ): void
    {
        $this->getClient()->repository()->contents()->create(
            $userName,
            $repositoryName,
            $file->path,
            $file->content,
            sprintf('Add %s file', $file->path),
            $branch,
        );
    }

    public function createFileFromUrl(
        string $userName,
        string $repositoryName,
        string $branch,
        string $path,
        string $url
    ): void
    {
        $gitHubUrlParser = GitHubUrlParser::fromUrl($url);
        $content = $this->downloadFile($gitHubUrlParser->owner, $gitHubUrlParser->repository, $gitHubUrlParser->path);
        $file = File::fromGithubContent($path, $url, $content);
        $this->createFile($userName, $repositoryName, $branch, $file);
    }

    public function downloadFile(
        string $userName,
        string $repositoryName,
        string $path,
    ): string
    {
        return $this->getClient()->repository()->contents()->download($userName, $repositoryName, $path,);
    }

    public function addContributor(
        string $userName,
        string $repositoryName,
        string $contributor,
    ): void
    {
        $this->getClient()->repository()->collaborators()->add($userName, $repositoryName, $contributor);
    }

    public function addContributors(
        string $userName,
        string $repositoryName,
        array $contributors,
    ): void
    {
        foreach ($contributors as $contributor) {
            $this->addContributor($userName, $repositoryName, $contributor);
        }
    }

    public function createMilestone(
        string $userName,
        string $repositoryName,
        Milestone $milestone,
    ): array
    {
        return $this->getClient()->issues()->milestones()->create($userName, $repositoryName, $milestone->toArray());
    }

    /**
     * @param string $userName
     * @param string $repositoryName
     * @param array|Milestone[] $milestones
     * @return array
     */
    public function createMilestones(
        string $userName,
        string $repositoryName,
        array $milestones,
    ): array
    {
        $githubMilestones = [];
        foreach ($milestones as $milestone) {
            $githubMilestone = $this->createMilestone($userName, $repositoryName, $milestone);
            $githubMilestones[$milestone->title] = $githubMilestone['number'];
        }
        return $githubMilestones;
    }

    public function enableAutomatedSecurityFixes(string $userName, string $repositoryName): void
    {
        $this->getClient()->repository()->enableAutomatedSecurityFixes($userName, $repositoryName);
    }

    public function enableAutomatedVulnerabilityAlerts(string $userName, string $repositoryName): void
    {
        $this->getClient()->repository()->enableVulnerabilityAlerts($userName, $repositoryName);
    }

    public function addCodeOwners(
        string $userName,
        string $repositoryName,
        array $codeOwners,
        string $branch,
    ): void
    {
        $codeowners = Codeowners::fromArray($codeOwners);
        $this->checkIfCodeownersFileCreationIsPossible($codeowners);

        $file = File::fromContent('.github/CODEOWNERS', $codeowners->generateCodeowners());
        $this->createFile($userName, $repositoryName, $branch, $file);
    }

    private function checkIfCodeownersFileCreationIsPossible(Codeowners $codeowners): void
    {
        foreach ($codeowners->listReviewers() as $reviewer) {
            try {
                $this->getClient()->user()->show($reviewer);
            } catch (\Exception $exception) {
                throw new \RuntimeException(
                    sprintf('Codeowner file not created because, user %s does not exist', $reviewer),
                    $exception->getCode(),
                    $exception
                );
            }
        }
    }

    public function createIssue(
        string $userName,
        string $repositoryName,
        Issue $issue,
        array $milestones = [],
    ): void
    {
        if ([] !== $milestones) {
            $issue = $issue->withMilestoneNumber($milestones[$issue->milestone]);
        }

        $this->getClient()->issues()->create($userName, $repositoryName, $issue->toArray());
    }

    public function createIssues(
        string $userName,
        string $repositoryName,
        array $issues,
        array $milestones = [],
    ): void
    {
        foreach ($issues as $issue) {
            $this->createIssue($userName, $repositoryName, $issue, $milestones);
        }
    }

    public function protectBranch(
        string $userName,
        string $repositoryName,
        string $branch,
        bool $requireCodeOwnerReviews = false
    ): void
    {
        $params = [
            'required_status_checks' => null,
            'enforce_admins' => null,
            'required_pull_request_reviews' => [
                'dismiss_stale_reviews' => true,
                'require_code_owner_reviews' => $requireCodeOwnerReviews,
                'required_approving_review_count' => 1,
            ],
            'restrictions' => null,
        ];

        $this->getClient()->repository()->protection()->update($userName, $repositoryName, $branch, $params );
    }

    // TODO Branch protection is available


}
