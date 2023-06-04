<?php

namespace App\DTO;

class Issue
{
    public bool $isGitHub;
    public string $fullPath;
    private ?int $milestoneNumber;

    private function __construct(
        public readonly string $title,
        public readonly string $content,
        public readonly array $labels,
        public readonly ?string $milestone,
    )
    {
        $this->milestoneNumber = null;
    }

    public static function fromPath(
        string $title,
        array $labels,
        string $fullPath,
        ?string $milestone = null,
    ): self
    {
        $issue = new self($title, @file_get_contents($fullPath), $labels, $milestone);
        $issue->isGitHub = false;
        return $issue;
    }

    public static function fromUrl(
        string $title,
        string $url,
        array $labels,
        ?string $milestone = null
    ): self
    {
        $file = new self($title, '', $labels, $milestone);
        $file->isGitHub = str_contains($url, 'github.com');
        $file->fullPath = $url;
        return $file;
    }

    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'body' => $this->content,
            'labels' => $this->labels,
            'milestone' => $this->milestoneNumber,
        ];
    }

    public function withMilestoneNumber(int $milestoneNumber): self
    {
        $issue = clone $this;
        $issue->milestoneNumber = $milestoneNumber;

        return $issue;
    }
}
