<?php

namespace App\DTO;

use Nyholm\Psr7\Uri;

readonly class GitHubUrlParser
{
    public string $owner;
    public string $repository;
    public string $path;

    public function __construct(
        public string $url,
    )
    {
    }

    public static function fromUrl(string $url): self
    {
        $fullPath = str_replace('blob/master/', '', $url);
        $uri = new Uri($fullPath);
        $uriParts = explode('/', trim($uri->getPath(), '/'));

        $gitHubUrlParser = new self($url);
        $gitHubUrlParser->owner = $uriParts[0];
        $gitHubUrlParser->repository = $uriParts[1];
        $gitHubUrlParser->path = implode('/', array_slice($uriParts, 2));
        return $gitHubUrlParser;
    }
}
