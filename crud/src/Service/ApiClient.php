<?php
declare(strict_types=1);

namespace App\Service;

use Cake\Core\Configure;
use Cake\Http\Client;
use Cake\Http\Client\Response;

class ApiClient
{
    private Client $client;
    private string $baseUrl;
    private string $token;

    public function __construct()
    {
        $this->client = new Client();
        $this->baseUrl = rtrim(Configure::read('Api.url'), '/');
        $this->token = Configure::read('Api.token');
    }

    private function headers(): array
    {
        return [
            'X-API-TOKEN' => $this->token,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
    }

    public function get(string $path, array $query = []): array
    {
        $url = $this->baseUrl . $path;
        $response = $this->client->get($url, $query, ['headers' => $this->headers()]);
        return $this->decode($response);
    }

    public function post(string $path, array $data = []): array
    {
        $url = $this->baseUrl . $path;
        $response = $this->client->post($url, json_encode($data), ['headers' => $this->headers()]);
        return $this->decode($response);
    }

    public function patch(string $path, array $data = []): array
    {
        $url = $this->baseUrl . $path;
        $response = $this->client->patch($url, json_encode($data), ['headers' => $this->headers()]);
        return $this->decode($response);
    }

    public function delete(string $path): array
    {
        $url = $this->baseUrl . $path;
        $response = $this->client->delete($url, [], ['headers' => $this->headers()]);
        return $this->decode($response);
    }

    public function postMultipart(string $path, array $data): array
    {
        $url = $this->baseUrl . $path;
        $headers = [
            'X-API-TOKEN' => $this->token,
            'Accept' => 'application/json',
        ];
        $response = $this->client->post($url, $data, ['headers' => $headers]);
        return $this->decode($response);
    }

    private function decode(Response $response): array
    {
        $body = $response->getStringBody();
        if (empty($body)) {
            return ['_status' => $response->getStatusCode()];
        }
        $data = json_decode($body, true) ?? [];
        $data['_status'] = $response->getStatusCode();
        return $data;
    }
}
