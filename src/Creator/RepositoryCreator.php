<?php

namespace App\Creator;

use App\Configuration\Configuration;
use App\DTO\File;
use App\DTO\GitHubUrlParser;
use App\DTO\Repository;
use Github\AuthMethod;
use Github\Client;
use Psr\Http\Client\ClientInterface;

readonly class RepositoryCreator
{
    private Client $client;

    public function __construct(
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

    public function create(Repository $repository): void
    {
        $this->createRepository($repository);
        $this->addFiles($repository);
        $this->addBranches($repository);
        $this->addLabels($repository);
        $this->addContributors($repository);
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
        return;
        foreach ($repository->contributors as $contributor) {
            $this->client->repository()->collaborators()->add(
                $this->configuration->user,
                $repository->getName(),
                $contributor,
            );
        }
    }
}
