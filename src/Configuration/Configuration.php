<?php

namespace App\Configuration;

use Symfony\Component\Yaml\Yaml;
use Webmozart\Assert\Assert;

readonly class Configuration
{
    public const VISIBILITY_PUBLIC = 'public';
    public const VISIBILITY_PRIVATE = 'private';
    public const NO_TEMPLATE = 'No template';

    private function __construct(
            public string $locale,
            public string $githubToken,
            public string $user,
            public ?string $defaultClientName,
            public ?string $defaultProjectName,
            public ?string $defaultProjectType,
            public ?string $defaultDescription,
            public ?string $defaultVisibility,
            public ?string $defaultMainBranch,
            public array $defaultContributors,
            public array $projectTypes,
            public string $mainBranch,
            public string $developBranch,
            public array $branches,
            public array $labels,
            public array $templates,
            public array $visibilities,
            public array $files,
            public array $milestones,
            public array $codeowners,
            public array $pullRequests,
            public array $issues,
    )
    {
    }

    public static function load(string $configFile): self
    {
        Assert::file($configFile, 'The config file does not exist.');

        $config = Yaml::parseFile($configFile)['create_repository'];

        Assert::keyExists($config, 'github_token');
        Assert::notNull($config['github_token'], 'The github_token cannot be null, please set it in the config file.');
        Assert::keyExists($config, 'user');
        Assert::notNull($config['user'], 'The user cannot be null, please set it in the config file.');
        Assert::keyExists($config, 'project_types');
        Assert::notNull($config['project_types'], 'There must be at least one project type, please set it in the config file.');
        Assert::keyExists($config, 'main_branch');
        Assert::notNull($config['main_branch'], 'The main_branch cannot be null, please set it in the config file.');
        Assert::keyExists($config, 'develop_branch');
        Assert::keyExists($config, 'branches');
        Assert::keyExists($config, 'labels');
        Assert::keyExists($config, 'files');
        Assert::keyExists($config, 'templates');
        Assert::keyExists($config, 'enable_no_template');
        Assert::keyExists($config, 'milestones');
        Assert::keyExists($config, 'codeowners');
        Assert::keyExists($config, 'pull_requests');
        Assert::keyExists($config, 'issues');

        $visibilities = [
            self::VISIBILITY_PUBLIC,
            self::VISIBILITY_PRIVATE,
        ];

        $templates = $config['templates'] ?? [];
        if ($config['enable_no_template']) {
            array_unshift($templates, [
                    'name' => self::NO_TEMPLATE,
                    'include_all_branches' => false,
                ]
            );
        }

        return new self(
            $config['locale'] ?? 'en',
            $config['github_token'],
            $config['user'],
            $config['defaults']['client_name'] ?? null,
            $config['defaults']['project_name'] ?? null,
            $config['defaults']['project_type'] ?? null,
            $config['defaults']['description'] ?? null,
            $config['defaults']['visibility'] ?? null,
            $config['defaults']['main_branch'] ?? null,
            $config['defaults']['contributors'] ?? [],
            $config['project_types'],
            $config['main_branch'],
            $config['develop_branch'],
            $config['branches'] ?? [],
            $config['labels'] ?? [],
            $config['templates'] ?? [],
            $visibilities,
            $config['files'] ?? [],
            $config['milestones'] ?? [],
            $config['codeowners'] ?? [],
            $config['pull_requests'] ?? [],
            $config['issues'] ?? [],
        );
    }

    public function getDefaultTemplate(): string
    {
        return current($this->getTemplates());
    }

    public function getTemplates(): array
    {
        return array_column($this->templates, 'name');
    }

    public function includeAllBranchesFromTemplate(string $templateName)
    {
        return current(
            array_filter(
                $this->templates,
                fn($template) => $template['name'] === $templateName
            )
        )['include_all_branches'] ?? false;
    }

    public function getCiChecks(): array
    {
        return $this->pullRequests['ci_checks'] ?? [];
    }
}
