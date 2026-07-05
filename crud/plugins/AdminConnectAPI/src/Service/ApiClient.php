<?php
declare(strict_types=1);

namespace AdminConnectAPI\Service;

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
        $response = $this->client->get(
            $this->baseUrl . $path,
            $query,
            ['headers' => $this->headers()]
        );
        return $this->decode($response);
    }

    public function post(string $path, array $data = []): array
    {
        $response = $this->client->post(
            $this->baseUrl . $path,
            json_encode($data),
            ['headers' => $this->headers()]
        );
        return $this->decode($response);
    }

    public function patch(string $path, array $data = []): array
    {
        $response = $this->client->patch(
            $this->baseUrl . $path,
            json_encode($data),
            ['headers' => $this->headers()]
        );
        return $this->decode($response);
    }

    public function delete(string $path): array
    {
        $response = $this->client->delete(
            $this->baseUrl . $path,
            [],
            ['headers' => $this->headers()]
        );
        return $this->decode($response);
    }

    public function getRaw(string $path): array
    {
        $response = $this->client->get(
            $this->baseUrl . $path,
            [],
            ['headers' => $this->headers()]
        );
        return [
            'body'         => $response->getStringBody(),
            'content_type' => $response->getHeaderLine('Content-Type') ?: 'application/octet-stream',
            '_status'      => $response->getStatusCode(),
        ];
    }

    public function postMultipart(string $path, array $data): array
    {
        $response = $this->client->post(
            $this->baseUrl . $path,
            $data,
            ['headers' => [
                'X-API-TOKEN' => $this->token,
                'Accept' => 'application/json',
            ]]
        );
        return $this->decode($response);
    }

    public function isSuccess(array $response): bool
    {
        return isset($response['_status']) && $response['_status'] >= 200 && $response['_status'] < 300;
    }

    private function decode(Response $response): array
    {
        $body = $response->getStringBody();
        if (empty($body)) {
            return ['_status' => $response->getStatusCode()];
        }
        $data = json_decode($body, true) ?? [];
        // Normalize: if API returns 'error' key but not 'message', copy it over
        if (!isset($data['message']) && isset($data['error'])) {
            $data['message'] = $data['error'];
        }
        $data['_status'] = $response->getStatusCode();
        return $data;
    }
}
