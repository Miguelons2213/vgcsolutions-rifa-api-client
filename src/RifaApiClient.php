<?php

namespace Rifa\ApiClient;

use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class RifaApiClient
{
    protected HttpFactory $http;
    protected string $baseUrl;
    protected string $publicKey;
    protected string $secret;
    protected int $signatureTtl;
    protected array $httpOptions;
    protected array $defaultHeaders;

    public function __construct(
        HttpFactory $http,
        string $baseUrl,
        string $publicKey,
        string $secret,
        int $signatureTtl = 60,
        array $httpOptions = [],
        array $defaultHeaders = []
    ) {
        $this->http = $http;
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->publicKey = $publicKey;
        $this->secret = $secret;
        $this->signatureTtl = max(1, $signatureTtl);
        $this->httpOptions = $httpOptions;
        $this->defaultHeaders = $defaultHeaders;
    }

    public function list(array $filters = []): array
    {
        return $this->get('/', $filters);
    }

    public function availability(int $rifaId, array $filters = []): array
    {
        return $this->get("/{$rifaId}/availability", $filters);
    }

    public function verifyNumber(int $rifaId, int $number): array
    {
        return $this->get("/{$rifaId}/verify-number", ['number' => $number]);
    }

    public function summary(int $rifaId): array
    {
        return $this->get("/{$rifaId}/summary");
    }

    public function get(string $path, array $query = []): array
    {
        return $this->request('GET', $path, ['query' => $query]);
    }

    public function post(string $path, array $payload = []): array
    {
        return $this->request('POST', $path, ['json' => $payload]);
    }

    public function request(string $method, string $path, array $options = []): array
    {
        $this->guardConfiguration();

        $method = Str::upper($method);
        $path = $this->normalizePath($path);
        $query = Arr::get($options, 'query', []);
        $bodyPayload = Arr::get($options, 'json', []);
        $queryString = $this->buildQueryString($query);
        $relativeUri = $queryString === '' ? $path : $path . '?' . $queryString;
        $body = $method === 'GET' ? '' : $this->encodeBody($bodyPayload);

        $headers = array_merge(
            $this->defaultHeaders,
            $this->buildSignatureHeaders($method, $relativeUri, $body)
        );

        /** @var PendingRequest $pendingRequest */
        $pendingRequest = $this->http->withHeaders($headers)->acceptJson();
        $request = $this->applyHttpOptions($pendingRequest);

        $url = $this->baseUrl . $path;

        $response = match ($method) {
            'GET' => $request->get($url, $query),
            'DELETE' => $request->delete($url, $bodyPayload),
            'PATCH' => $request->patch($url, $bodyPayload),
            'PUT' => $request->put($url, $bodyPayload),
            default => $request->post($url, $bodyPayload),
        };

        return $response->throw()->json();
    }

    protected function buildSignatureHeaders(string $method, string $uri, string $body = ''): array
    {
        $timestamp = now()->timestamp;
        $payload = implode('|', [$timestamp, $method, $uri, $body]);
        $signature = hash_hmac('sha256', $payload, $this->secret);

        return [
            'X-Rifa-Api-Key' => $this->publicKey,
            'X-Rifa-Timestamp' => $timestamp,
            'X-Rifa-Signature' => $signature,
        ];
    }

    protected function normalizePath(string $path): string
    {
        $stringable = Str::of($path);
        $normalized = $stringable->start('/')
            ->replace('//', '/');

        return (string) $normalized;
    }

    protected function buildQueryString(array $query): string
    {
        if (empty($query)) {
            return '';
        }

        ksort($query);

        return http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    }

    protected function encodeBody(array $body): string
    {
        if (empty($body)) {
            return '';
        }

        return json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    protected function applyHttpOptions(PendingRequest $request): PendingRequest
    {
        $timeout = Arr::get($this->httpOptions, 'timeout');
        $connectTimeout = Arr::get($this->httpOptions, 'connect_timeout');
        $retryTimes = (int) Arr::get($this->httpOptions, 'retry.times', 1);
        $retrySleep = (int) Arr::get($this->httpOptions, 'retry.sleep', 100);

        if ($timeout) {
            $request = $request->timeout($timeout);
        }

        if ($connectTimeout) {
            $request = $request->connectTimeout($connectTimeout);
        }

        if ($retryTimes > 1) {
            $request = $request->retry($retryTimes, $retrySleep);
        }

        return $request;
    }

    protected function guardConfiguration(): void
    {
        if ($this->baseUrl === '' || $this->publicKey === '' || $this->secret === '') {
            throw new \RuntimeException('Rifa API client is not configured.');
        }
    }
}
