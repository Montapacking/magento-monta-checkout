<?php

namespace Montapacking\MontaCheckout\Test\Unit;

use Magento\Framework\Module\Manager;
use Monta\CheckoutApiWrapper\MontapackingShipping;
use Magento\Framework\App\DeploymentConfig;

use PHPUnit\Framework\TestCase;
use Monta\CheckoutApiWrapper\Objects\Settings;

final class Test extends TestCase
{
    public function TestGetDeliveryOptionsAndPickupPoints(): void
    {
        $settings = new Settings($_ENV['SHOP'], $_ENV['USERNAME'], $_ENV['PASSWORD'], true, 10, $_ENV['GOOGLEAPIKEY'], 2);

        $api = new MontapackingShipping($settings, 'nl-NL');

        $api->setAddress(
            'Papland',
            16,
            '',
            '4206L',
            'Gorinchem',
            '',
            'NL'
        );

        $api->addProduct('croc', 1);

        $results = $api->getShippingOptions();

        $this->assertTrue(sizeof($results['DeliveryOptions']) > 0);
        $this->assertTrue(sizeof($results['PickupOptions']) > 0);
    }
}


