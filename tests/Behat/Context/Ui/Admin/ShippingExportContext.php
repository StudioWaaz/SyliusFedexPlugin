<?php

declare(strict_types=1);

namespace Tests\Waaz\SyliusFedexPlugin\Behat\Context\Ui\Admin;

use Behat\Behat\Context\Context;
use Tests\BitBag\SyliusShippingExportPlugin\Behat\Page\Admin\ShippingExport\IndexPageInterface;
use Tests\Waaz\SyliusFedexPlugin\Behat\Mocker\FedexApiMocker;

final class ShippingExportContext implements Context
{
    private IndexPageInterface $indexPage;

    private FedexApiMocker $fedexApiMocker;

    public function __construct(
        IndexPageInterface $indexPage,
        FedexApiMocker $fedexApiMocker,
    ) {
        $this->fedexApiMocker = $fedexApiMocker;
        $this->indexPage = $indexPage;
    }

    /**
     * @When I export all new shipments to fedex api
     */
    public function iExportAllNewShipments(): void
    {
        $this->fedexApiMocker->performActionInApiSuccessfulScope(function () {
            $this->indexPage->exportAllShipments();
        });
    }

    /**
     * @When I export first shipment to fedex api
     */
    public function iExportFirsShipments(): void
    {
        $this->fedexApiMocker->performActionInApiSuccessfulScope(function () {
            $this->indexPage->exportFirsShipment();
        });
    }
}
