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
        $clientId = trim($clientId);
        $clientSecret = trim($clientSecret);

        $cacheKey = 'fedex_token_' . md5($clientId . '_' . ($sandbox ? 'sandbox' : 'prod'));

        if ($this->cache !== null) {
            $item = $this->cache->getItem($cacheKey);
            if ($item->isHit()) {
                /** @var mixed $cachedToken */
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
            /** @var array<string, mixed> $data */
            $data = $response->toArray(false);

            if ($statusCode >= 400 || !isset($data['access_token'])) {
                /** @var mixed $errors */
                $errors = $data['errors'] ?? null;
                /** @var mixed $firstError */
                $firstError = \is_array($errors) ? ($errors[0] ?? null) : null;
                /** @var mixed $msgVal */
                $msgVal = \is_array($firstError) ? ($firstError['message'] ?? null) : null;
                /** @var mixed $errorMsgMixed */
                $errorMsgMixed = $msgVal ?? $data['message'] ?? 'Authentication failed with FedEx API.';
                $errorMessage = \is_string($errorMsgMixed) ? $errorMsgMixed : 'Authentication failed with FedEx API.';

                if ($statusCode === 401) {
                    $envName = $sandbox ? 'Sandbox (Test)' : 'Production';
                    $errorMessage .= sprintf(' (Target endpoint: %s [%s]. Verify that your API Key & Secret match this environment on developer.fedex.com)', $baseUrl, $envName);
                }

                throw new FedexApiException($errorMessage, $statusCode, $data);
            }

            /** @var mixed $tokenVal */
            $tokenVal = $data['access_token'];
            $token = \is_string($tokenVal) ? $tokenVal : '';
            /** @var mixed $expiresInVal */
            $expiresInVal = $data['expires_in'] ?? null;
            $expiresIn = \is_int($expiresInVal) || \is_numeric($expiresInVal) ? (int) $expiresInVal : 3600;

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
            /** @var array<string, mixed> $data */
            $data = $response->toArray(false);

            if ($statusCode >= 400) {
                /** @var mixed $errors */
                $errors = $data['errors'] ?? null;
                /** @var mixed $firstError */
                $firstError = \is_array($errors) ? ($errors[0] ?? null) : null;
                /** @var mixed $msgVal */
                $msgVal = \is_array($firstError) ? ($firstError['message'] ?? null) : null;
                /** @var mixed $errorMsgMixed */
                $errorMsgMixed = $msgVal ?? 'Failed to retrieve FedEx locations.';
                $errorMessage = \is_string($errorMsgMixed) ? $errorMsgMixed : 'Failed to retrieve FedEx locations.';

                throw new FedexApiException($errorMessage, $statusCode, $data);
            }

            /** @var mixed $output */
            $output = $data['output'] ?? [];
            if (!\is_array($output)) {
                $output = [];
            }
            /** @var array<int, array<string, mixed>> $locationDetails */
            $locationDetails = $output['locationDetailList'] ?? [];

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
            /** @var array<string, mixed> $data */
            $data = $response->toArray(false);

            if ($statusCode >= 400) {
                /** @var mixed $errors */
                $errors = $data['errors'] ?? null;
                /** @var mixed $firstError */
                $firstError = \is_array($errors) ? ($errors[0] ?? null) : null;
                /** @var mixed $msgVal */
                $msgVal = \is_array($firstError) ? ($firstError['message'] ?? null) : null;
                /** @var mixed $errorMsgMixed */
                $errorMsgMixed = $msgVal ?? 'FedEx shipment creation failed.';
                $errorMessage = \is_string($errorMsgMixed) ? $errorMsgMixed : 'FedEx shipment creation failed.';

                throw new FedexApiException($errorMessage, $statusCode, $data);
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
