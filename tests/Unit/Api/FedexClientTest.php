<?php

declare(strict_types=1);

namespace Tests\Waaz\SyliusFedexPlugin\Unit\Api;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Waaz\SyliusFedexPlugin\Api\Exception\FedexApiException;
use Waaz\SyliusFedexPlugin\Api\FedexClient;

final class FedexClientTest extends TestCase
{
    public function testGetAccessToken(): void
    {
        $mockResponse = new MockResponse(json_encode([
            'access_token' => 'test_token_123',
            'token_type' => 'bearer',
            'expires_in' => 3600,
        ], \JSON_THROW_ON_ERROR));

        $httpClient = new MockHttpClient([$mockResponse]);
        $client = new FedexClient($httpClient);

        $token = $client->getAccessToken('dummy_key', 'dummy_secret', true);
        $this->assertEquals('test_token_123', $token);
    }

    public function testSearchLocations(): void
    {
        $authResponse = new MockResponse(json_encode([
            'access_token' => 'test_token_123',
            'expires_in' => 3600,
        ], \JSON_THROW_ON_ERROR));

        $locationsResponse = new MockResponse(json_encode([
            'output' => [
                'locationDetailList' => [
                    [
                        'locationId' => 'LOC_001',
                        'locationContactAndAddress' => [
                            'contact' => ['companyName' => 'Relais FedEx Paris'],
                            'address' => [
                                'streetLines' => ['1 Rue de Rivoli'],
                                'city' => 'Paris',
                                'postalCode' => '75001',
                                'countryCode' => 'FR',
                                'geoCoordinates' => ['latitude' => 48.8566, 'longitude' => 2.3522],
                            ],
                        ],
                    ],
                ],
            ],
        ], \JSON_THROW_ON_ERROR));

        $httpClient = new MockHttpClient([$authResponse, $locationsResponse]);
        $client = new FedexClient($httpClient, null, 'default_key', 'default_secret', true);

        $locations = $client->searchLocations('75001', 'FR');
        $this->assertCount(1, $locations);
        $this->assertEquals('LOC_001', $locations[0]['locationId']);
    }

    public function testCreateShipment(): void
    {
        $authResponse = new MockResponse(json_encode([
            'access_token' => 'test_token_123',
            'expires_in' => 3600,
        ], \JSON_THROW_ON_ERROR));

        $shipmentResponse = new MockResponse(json_encode([
            'output' => [
                'transactionShipments' => [
                    [
                        'masterTrackingNumber' => '794611112222',
                        'pieceResponses' => [
                            [
                                'packageDocuments' => [
                                    [
                                        'contentType' => 'application/pdf',
                                        'encodedLabel' => base64_encode('PDF_RAW_CONTENT'),
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ], \JSON_THROW_ON_ERROR));

        $httpClient = new MockHttpClient([$authResponse, $shipmentResponse]);
        $client = new FedexClient($httpClient);

        $data = $client->createShipment(['some' => 'payload'], 'key', 'secret', true);
        $this->assertArrayHasKey('output', $data);
        $this->assertEquals('794611112222', $data['output']['transactionShipments'][0]['masterTrackingNumber']);
    }

    public function testAuthFailureThrowsFedexApiException(): void
    {
        $errorResponse = new MockResponse(json_encode([
            'errors' => [
                ['message' => 'Invalid client credentials'],
            ],
        ], \JSON_THROW_ON_ERROR), ['http_code' => 401]);

        $httpClient = new MockHttpClient([$errorResponse]);
        $client = new FedexClient($httpClient);

        $this->expectException(FedexApiException::class);
        $client->getAccessToken('wrong_key', 'wrong_secret', true);
    }
}
