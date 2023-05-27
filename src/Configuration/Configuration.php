<?php

namespace App\Configuration;

use Symfony\Component\Yaml\Yaml;
use Webmozart\Assert\Assert;

readonly class Configuration
{
    public const VISIBILITY_PUBLIC = 'public';
    public const VISIBILITY_PRIVATE = 'private';
    public const NO_TEMPLATE = 'No template';

    public string $locale;
    public string $githubToken;
    public string $user;

    // Defaults
    public ?string $defaultClientName;
    public ?string $defaultProjectName;
    public ?string $defaultProjectType;
    public ?string $defaultDescription;
    public ?string $defaultVisibility;
    public ?string $defaultMainBranch;
    public array $defaultContributors;
    public array $projectTypes;
    public string $mainBranch;
    public string $developBranch;
    public array $branches;
    public array $labels;
    public array $templates;
    public array $visibilities;
    public array $files;

    public function __construct()
    {
        $configFile = __DIR__ . '/../../config/config.yaml';
        Assert::file($configFile, 'The config file does not exist.');

        $config = Yaml::parseFile($configFile)['config'];

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

        $this->locale = $config['locale'] ?? 'en';
        $this->githubToken = $config['github_token'];
        $this->user = $config['user'];
        $this->defaultClientName = $config['defaults']['client_name'] ?? null;
        $this->defaultProjectName = $config['defaults']['project_name'] ?? null;
        $this->defaultProjectType = $config['defaults']['project_type'] ?? null;
        $this->defaultDescription = $config['defaults']['description'] ?? null;
        $this->defaultVisibility = $config['defaults']['visibility'] ?? null;
        $this->defaultMainBranch = $config['defaults']['main_branch'] ?? null;
        $this->projectTypes = $config['project_types'];
        $this->defaultContributors = $config['defaults']['contributors'] ?? [];
        $this->mainBranch = $config['main_branch'];
        $this->developBranch = $config['develop_branch'];
        $this->branches = $config['branches'] ?? [];
        $this->labels = $config['labels'] ?? [];
        $this->files = $config['files'] ?? [];
        $this->templates = $templates;
        $this->visibilities = $visibilities;
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
}
