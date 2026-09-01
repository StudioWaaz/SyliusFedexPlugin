<?php

declare(strict_types=1);

namespace Waaz\SyliusFedexPlugin\Api;

interface FedexClientInterface
{
    public const SANDBOX_URL = 'https://apis-sandbox.fedex.com';

    public const PRODUCTION_URL = 'https://apis.fedex.com';

    /**
     * Authenticates and returns Bearer access token
     */
    public function getAccessToken(string $clientId, string $clientSecret, bool $sandbox = true): string;

    /**
     * Searches FedEx pickup/drop-off locations nearby
     *
     * @return array<int, array<string, mixed>>
     */
    public function searchLocations(
        string $postalCode,
        string $countryCode,
        ?string $city = null,
        ?string $address = null,
        int $radiusKm = 50,
        ?string $clientId = null,
        ?string $clientSecret = null,
        bool $sandbox = true,
    ): array;

    /**
     * Creates a shipment and returns API response with tracking number and labels
     *
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function createShipment(
        array $payload,
        string $clientId,
        string $clientSecret,
        bool $sandbox = true,
    ): array;
}
