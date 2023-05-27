<?php

namespace App\DTO;

use App\Configuration\Configuration;
use Symfony\Component\String\Slugger\AsciiSlugger;

class Repository
{
    public ?string $organization;
    public ?string $template;
    public string $description;
    public string $visibility;
    /** @var array|File[] */
    public array $files = [];
    /** @var array|Branch[] */
    public array $branches = [];
    /** @var array|Label[] */
    public array $labels = [];
    /** @var array|Milestone[] */
    public array $milestones = [];
    /** @var string[] */
    public array $contributors = [];
    public readonly string $defaultBranch;

    private function __construct(
        public string $clientName,
        public string $projectName,
        public string $projetType
    )
    {
    }

    public static function create(
        string $clientName,
        string $projectName,
        string $projetType,
    ): self
    {
        return new self(
            $clientName,
            $projectName,
            $projetType
        );
    }

    public function getName(): string
    {
        $name = sprintf(
            '%s-%s-%s',
            $this->clientName,
            $this->projectName,
            $this->projetType
        );
        return (new AsciiSlugger())
            ->slug($name)
            ->lower();
    }

    private function addFile(File $file): void
    {
        $this->files[] = $file;
    }

    private function addBranch(Branch $branch): void
    {
        $this->branches[] = $branch;
    }

    private function addLabel(Label $label): void
    {
        $this->labels[] = $label;
    }

    private function addMilestone(Milestone $milestone): void
    {
        $this->milestones[] = $milestone;
    }

    private function addContributor(string $contributor): void
    {
        $this->contributors[] = $contributor;
    }

    public function withFile(string|array $name, string $locale): self
    {
        $repository = clone $this;

        if (is_array($name)) {
            $repository->addFile(File::fromUrl($name['path'], $name['url']));
            return $repository;
        }

        $repository->addFile(File::fromPath($name, $locale));
        return $repository;
    }

    public function withDefaultBranch(string $name): self
    {
        $repository = clone $this;
        $repository->defaultBranch = $name;
        $repository->addBranch(Branch::create($name, true));

        return $repository;
    }

    public function withBranch(string $name): self
    {
        $repository = clone $this;
        $repository->addBranch(Branch::create($name));

        return $repository;
    }

    public function withLabel(string $name, string $color, ?string $description = null): self
    {
        $repository = clone $this;
        $repository->addLabel(Label::create($name, $color, $description));

        return $repository;
    }

    public function withMilestone(string $title, ?string $description, ?string $dueOn): self
    {
        $repository = clone $this;
        $repository->addMilestone(Milestone::create($title, $description, $dueOn));

        return $repository;
    }

    public function withContributor(string $name): self
    {
        $repository = clone $this;
        $repository->addContributor($name);

        return $repository;
    }

    public function getTemplateOwner(): string
    {
        return explode('/', $this->template)[0];
    }

    public function getTemplateRepo(): string
    {
        return explode('/', $this->template)[1];
    }

    public function noTemplate(): bool
    {
        if (null === $this->template) {
            return true;
        }

        return $this->template === Configuration::NO_TEMPLATE;
    }

    public function withLabels(array $labels): self
    {
        $repository = clone $this;
        foreach ($labels as $label) {
            $repository = $repository->withLabel($label['name'], $label['color'], $label['description'] ?? null);
        }

        return $repository;
    }

    public function withFiles(array $files, string $locale): self
    {
        $repository = clone $this;
        foreach ($files as $file) {
            $repository = $repository->withFile($file, $locale);
        }

        return $repository;
    }

    public function withContributors(array $contributors): self
    {
        $repository = clone $this;
        foreach ($contributors as $contributor) {
            $repository = $repository->withContributor($contributor);
        }

        return $repository;
    }

    public function withDescription(string $description): self
    {
        $repository = clone $this;
        $repository->description = $description;

        return $repository;
    }

    public function withTemplate(?string $template): self
    {
        $repository = clone $this;
        $repository->template = $template;

        return $repository;
    }

    public function withVisibility(string $visibility): self
    {
        $repository = clone $this;
        $repository->visibility = $visibility;

        return $repository;
    }

    public function withOrganization(?string $organization): self
    {
        $repository = clone $this;
        $repository->organization = $organization;

        return $repository;
    }

    public function withMilstones(array $milestones): self
    {
        $repository = clone $this;
        foreach ($milestones as $milestone) {
            $repository = $repository->withMilestone($milestone['title'], $milestone['description'], $milestone['due_on']);
        }

        return $repository;
    }

    /**
     * @return array|File[]
     */
    public function getFiles(): array
    {
        return array_filter($this->files, fn($file) => !$file->isGitHub);
    }

    /**
     * @return array|File[]
     */
    public function getGithubFiles(): array
    {
        return array_filter($this->files, fn($file) => $file->isGitHub);
    }
}
