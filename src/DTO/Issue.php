<?php

namespace App\DTO;

use Webmozart\Assert\Assert;

readonly class Issue
{
    public bool $isGitHub;
    public string $fullPath;

    private function __construct(
        public string $title,
        public string $content,
        public array $labels,
        public ?string $milestone,
    )
    {
    }

    public static function fromPath(
        string $title,
        string $path,
        array $labels,
        string $locale,
        ?string $milestone = null,
    ): self
    {
        $fullPath = sprintf('%s/../../issues/%s/%s', __DIR__, $locale, $path);
        Assert::file($fullPath, sprintf('The file "%s" does not exist.', $path));
        $issue = new self(
            $title,
            file_get_contents($fullPath),
            $labels,
            $milestone
        );
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
        ];
    }
}
