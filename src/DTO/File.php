<?php

namespace App\DTO;

use Symfony\Component\Finder\SplFileInfo;
use Webmozart\Assert\Assert;

class File
{
    private SplFileInfo $file;

    private function __construct(string $path)
    {
        $fullPath = __DIR__ . '/../../templates/' . $path;
        Assert::file($fullPath, sprintf('The file "%s" does not exist.', $path));
        $this->file = new SplFileInfo($fullPath, $path, $path);
    }

    public static function create(string $path): self
    {
        return new self($path);
    }

    public function getRelativePathname(): string
    {
        return $this->file->getRelativePathname();
    }

    public function getFilename(): string
    {
        return $this->file->getFilename();
    }

    public function getContent(): string
    {
        return $this->file->getContents();
    }
}
