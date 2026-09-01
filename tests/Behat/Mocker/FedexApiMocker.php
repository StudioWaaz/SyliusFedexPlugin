<?php

declare(strict_types=1);

namespace Tests\Waaz\SyliusFedexPlugin\Behat\Mocker;

use PSS\SymfonyMockerContainer\DependencyInjection\MockerContainer;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Waaz\SyliusFedexPlugin\Api\FedexClientInterface;

class FedexApiMocker
{
    /**
     * @param MockerContainer&ContainerInterface $container
     */
    public function __construct(private ContainerInterface $container)
    {
    }

    public function performActionInApiSuccessfulScope(callable $action): void
    {
        $this->mockApiSuccessfulFedexResponse();
        $action();
        if ($this->container instanceof MockerContainer) {
            $this->container->unmock('waaz.fedex_plugin.api.client');
        }
    }

    private function mockApiSuccessfulFedexResponse(): void
    {
        if (!$this->container instanceof MockerContainer) {
            return;
        }

        $mock = $this->container->mock(
            'waaz.fedex_plugin.api.client',
            FedexClientInterface::class,
        );

        $mock->shouldReceive('getAccessToken')->andReturn('mock_token');

        $mock->shouldReceive('createShipment')
            ->andReturn([
                'output' => [
                    'transactionShipments' => [
                        [
                            'masterTrackingNumber' => '794611112222',
                            'pieceResponses' => [
                                [
                                    'packageDocuments' => [
                                        [
                                            'contentType' => 'application/pdf',
                                            'encodedLabel' => base64_encode('MOCK_FEDEX_LABEL_PDF'),
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ]);
    }
}
