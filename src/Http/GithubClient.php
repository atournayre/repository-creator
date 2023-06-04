<?php

namespace App\Http;

use Github\AuthMethod;
use Github\Client;
use Psr\Http\Client\ClientInterface;

class GithubClient
{
    private Client $client;

    public function __construct(
        private readonly ClientInterface $httplugClient,
        string $githubToken,
    )
    {
        $this->client = Client::createWithHttpClient($this->httplugClient);
        $this->client->authenticate($githubToken, null, AuthMethod::ACCESS_TOKEN);
    }

    public function getClient(): Client
    {
        return $this->client;
    }
}
