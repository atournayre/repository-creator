<?php

namespace App\Service\Github;

use App\Configuration\Configuration;
use App\DTO\File;
use App\DTO\Repository;

readonly class CreateGithubRepositoryService
{
    public function __construct(
        private GithubService $githubService,
    )
    {
    }

    public function __invoke(Configuration $configuration, Repository $repository): void
    {
        $this->createRepository($configuration, $repository);
        $this->createFiles($configuration, $repository);
        $this->createFolders($configuration, $repository);
        $this->protectMainBranch($configuration, $repository);
        $this->githubService->createLabels($configuration->user, $repository->getName(), $repository->labels);
        $this->githubService->addContributors($configuration->user, $repository->getName(), $repository->contributors);
        $this->githubService->createMilestones($configuration->user, $repository->getName(), $repository->milestones);
        $this->githubService->enableAutomatedVulnerabilityAlerts($configuration->user, $repository->getName());
        $this->githubService->enableAutomatedSecurityFixes($configuration->user, $repository->getName());
        $this->githubService->addCodeOwners($configuration->user, $repository->getName(), $repository->codeOwners, $repository->defaultBranch);
        $this->githubService->createIssues($configuration->user, $repository->getName(), $repository->issues);
    }

    private function createRepository(Configuration $configuration, Repository $repository): void
    {
        if ($repository->noTemplate()) {
            $this->doCreateRepository($repository);
            return;
        }
        $this->doCreateRepositoryWithTemplate($configuration, $repository);
    }

    private function doCreateRepository(Repository $repository): void
    {
        $this->githubService->createRepository(
            $repository->getName(),
            $repository->description,
            $repository->visibility === Configuration::VISIBILITY_PUBLIC,
        );
    }

    private function doCreateRepositoryWithTemplate(Configuration $configuration, Repository $repository): void
    {
        $this->githubService->createRepositoryFromTemplate(
            $repository->getTemplateOwner(),
            $repository->getTemplateRepo(),
            $configuration->user,
            $repository->getName(),
            $repository->description,
            $repository->visibility === Configuration::VISIBILITY_PRIVATE,
            $configuration->includeAllBranchesFromTemplate($repository->template),
        );
    }

    private function createFiles(Configuration $configuration, Repository $repository): void
    {
        foreach ($repository->getGithubFiles() as $file) {
            $this->githubService->createFileFromUrl(
                $configuration->user,
                $repository->getName(),
                $repository->defaultBranch,
                $file,
                $file->fullPath
            );
        }

        foreach ($repository->getFiles() as $file) {
            $this->githubService->createFile(
                $configuration->user,
                $repository->getName(),
                $repository->defaultBranch,
                $file,
            );
        }
    }

    private function createFolders(Configuration $configuration, Repository $repository): void
    {
        foreach ($repository->folders as $folder) {
            $this->githubService->createFile(
                $configuration->user,
                $repository->getName(),
                $repository->defaultBranch,
                $folder,
            );
        }
    }

    private function protectMainBranch(Configuration $configuration, Repository $repository): void
    {
        if ($repository->isPrivate()) {
            return;
        }

        $this->githubService->protectBranch(
            $configuration->user,
            $repository->getName(),
            $repository->defaultBranch,
            $repository->isPublic() || $repository->requireCodeOwnerReviews(),
        );
    }
}
