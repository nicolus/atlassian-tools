<?php
namespace App;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AtlassianClient
{
    protected HttpClientInterface $client;

    public function __construct(string $uri, string $username, string $apiToken)
    {
        $this->client = HttpClient::createForBaseUri($uri . '/rest/', [
            'headers' => ['Accept' => 'application/json'],
            'auth_basic' => [$username, $apiToken],
        ]);
    }

    public function get($uri, $queryParams = [])
    {
        $response = $this->client->request('GET', $uri, ['query' => $queryParams]);
        return json_decode($response->getContent(), false, 512, JSON_THROW_ON_ERROR);
    }

    public function getPaginated($uri, $queryParams = []): \Generator
    {
        $queryParams['startAt'] = 0;
        do {
            $result = $this->get($uri, $queryParams);
            yield $result;
            $queryParams['startAt'] += $result->maxResults;
        } while ($result->startAt + $result->maxResults < $result->total);
    }
}
