<?php

namespace Eugenevdm;

use GuzzleHttp\Exception\GuzzleException;

class HelloPeterClient
{
    private $apiKey;
    private $client;

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
        $this->client = new \GuzzleHttp\Client([
            'base_uri' => 'https://api.hellopeter.com/v5/api/',
            'headers' => [
                'Accept' => 'application/vnd.api+json',
                'Content-Type' => 'application/json',
                'apiKey' => $this->apiKey
            ]
        ]);
    }

    /**
     * Get reviews with optional status filter
     *
     * @param string|null $status Status filter (e.g., 'unreplied,unreplied_comment'), null for all reviews
     * @return array
     * @throws GuzzleException
     */
    public function getReviews(?string $status = 'unreplied,unreplied_comment'): array
    {
        $query = ['channel' => 'HELLOPETER'];

        if ($status !== null) {
            $query['status'] = $status;
        }

        $response = $this->client->get('reviews', ['query' => $query]);

        return json_decode($response->getBody(), true);
    }

    /**
     * Get unreplied reviews
     *
     * @return array
     * @throws GuzzleException
     */
    public function getUnrepliedReviews(): array
    {
        return $this->getReviews('unreplied,unreplied_comment');
    }
} 