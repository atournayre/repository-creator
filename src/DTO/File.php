<?php

namespace App\DTO;

readonly class File
{
    public bool $isGitHub;
    public string $fullPath;

    private function __construct(
        public string $path,
        public string $content,
    )
    {
    }

    public static function fromPath(string $path, string $fullPath): self
    {
        $file = new self($path, @file_get_contents($fullPath));
        $file->isGitHub = false;
        $file->fullPath = $fullPath;
        return $file;
    }

    public static function fromUrl(string $path, string $url): self
    {
        $file = new self($path, '');
        $file->isGitHub = str_contains($url, 'github.com');
        $file->fullPath = $url;
        return $file;
    }

    public static function create(string $path, string $fullPath, ?string $content): self
    {
        if (null === $content) {
            return self::fromPath($path, $fullPath);
        }
        return new self($path, $content);
    }
}
