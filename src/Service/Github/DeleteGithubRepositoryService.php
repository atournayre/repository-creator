<?php

namespace App\Service\Github;

use App\Configuration\Configuration;
use App\DTO\Repository;

readonly class DeleteGithubRepositoryService
{

    public function __construct(
        private GithubService $githubService,
    )
    {
    }

    public function __invoke(Configuration $configuration, Repository $repository): void
    {
        $this->githubService->deleteRepository($configuration->user, $repository->getName());
    }
}
