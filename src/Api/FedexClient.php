<?php

declare(strict_types=1);

namespace Waaz\SyliusFedexPlugin\Api;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Waaz\SyliusFedexPlugin\Api\Exception\FedexApiException;

class FedexClient implements FedexClientInterface
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private ?CacheItemPoolInterface $cache = null,
        private ?string $defaultClientId = null,
        private ?string $defaultClientSecret = null,
        private bool $defaultSandbox = true,
    ) {
    }

    public function getAccessToken(string $clientId, string $clientSecret, bool $sandbox = true): string
    {
        $cacheKey = 'fedex_token_' . md5($clientId . '_' . ($sandbox ? 'sandbox' : 'prod'));

        if ($this->cache !== null) {
            $item = $this->cache->getItem($cacheKey);
            if ($item->isHit()) {
                $cachedToken = $item->get();
                if (\is_string($cachedToken) && $cachedToken !== '') {
                    return $cachedToken;
                }
            }
        }

        $baseUrl = $sandbox ? self::SANDBOX_URL : self::PRODUCTION_URL;

        try {
            $response = $this->httpClient->request('POST', $baseUrl . '/oauth/token', [
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
                'body' => [
                    'grant_type' => 'client_credentials',
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                ],
            ]);

            $statusCode = $response->getStatusCode();
            $data = $response->toArray(false);

            if ($statusCode >= 400 || !isset($data['access_token'])) {
                $errorMessage = $data['errors'][0]['message'] ?? $data['message'] ?? 'Authentication failed with FedEx API.';

                throw new FedexApiException((string) $errorMessage, $statusCode, $data);
            }

            $token = (string) $data['access_token'];
            $expiresIn = isset($data['expires_in']) ? (int) $data['expires_in'] : 3600;

            if ($this->cache !== null) {
                $item = $this->cache->getItem($cacheKey);
                $item->set($token);
                // Expire 60s before token actual expiry
                $item->expiresAfter(max(60, $expiresIn - 60));
                $this->cache->save($item);
            }

            return $token;
        } catch (HttpExceptionInterface $e) {
            throw new FedexApiException('FedEx OAuth HTTP Error: ' . $e->getMessage(), $e->getCode(), [], $e);
        } catch (\Throwable $e) {
            if ($e instanceof FedexApiException) {
                throw $e;
            }

            throw new FedexApiException('FedEx Auth Error: ' . $e->getMessage(), 0, [], $e);
        }
    }

    public function searchLocations(
        string $postalCode,
        string $countryCode,
        ?string $city = null,
        ?string $address = null,
        int $radiusKm = 50,
        ?string $clientId = null,
        ?string $clientSecret = null,
        bool $sandbox = true,
    ): array {
        $clientId = $clientId ?? $this->defaultClientId;
        $clientSecret = $clientSecret ?? $this->defaultClientSecret;
        $sandbox = $clientId !== null ? $sandbox : $this->defaultSandbox;

        if ($clientId === null || $clientId === '' || $clientSecret === null || $clientSecret === '') {
            throw new FedexApiException('FedEx Client ID and Client Secret are required for location search.');
        }

        $token = $this->getAccessToken($clientId, $clientSecret, $sandbox);
        $baseUrl = $sandbox ? self::SANDBOX_URL : self::PRODUCTION_URL;

        $addressPayload = [
            'postalCode' => $postalCode,
            'countryCode' => strtoupper($countryCode),
        ];

        if ($city !== null && $city !== '') {
            $addressPayload['city'] = $city;
        }

        if ($address !== null && $address !== '') {
            $addressPayload['streetLines'] = [$address];
        }

        $body = [
            'locationsSummaryRequestControlParameters' => [
                'distance' => [
                    'units' => 'KM',
                    'value' => $radiusKm,
                ],
            ],
            'locationSearchCriterion' => 'ADDRESS',
            'location' => [
                'address' => $addressPayload,
            ],
        ];

        try {
            $response = $this->httpClient->request('POST', $baseUrl . '/location/v1/locations', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json',
                ],
                'json' => $body,
            ]);

            $statusCode = $response->getStatusCode();
            $data = $response->toArray(false);

            if ($statusCode >= 400) {
                $errorMessage = $data['errors'][0]['message'] ?? 'Failed to retrieve FedEx locations.';

                throw new FedexApiException((string) $errorMessage, $statusCode, $data);
            }

            /** @var array<int, array<string, mixed>> $locationDetails */
            $locationDetails = $data['output']['locationDetailList'] ?? [];

            return $locationDetails;
        } catch (\Throwable $e) {
            if ($e instanceof FedexApiException) {
                throw $e;
            }

            throw new FedexApiException('FedEx Location Search Error: ' . $e->getMessage(), 0, [], $e);
        }
    }

    public function createShipment(
        array $payload,
        string $clientId,
        string $clientSecret,
        bool $sandbox = true,
    ): array {
        $token = $this->getAccessToken($clientId, $clientSecret, $sandbox);
        $baseUrl = $sandbox ? self::SANDBOX_URL : self::PRODUCTION_URL;

        try {
            $response = $this->httpClient->request('POST', $baseUrl . '/ship/v1/shipments', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
            ]);

            $statusCode = $response->getStatusCode();
            $data = $response->toArray(false);

            if ($statusCode >= 400) {
                $errorMessage = $data['errors'][0]['message'] ?? 'FedEx shipment creation failed.';

                throw new FedexApiException((string) $errorMessage, $statusCode, $data);
            }

            return $data;
        } catch (\Throwable $e) {
            if ($e instanceof FedexApiException) {
                throw $e;
            }

            throw new FedexApiException('FedEx Shipment Error: ' . $e->getMessage(), 0, [], $e);
        }
    }
}
