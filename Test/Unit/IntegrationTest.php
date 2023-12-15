<?php declare(strict_types=1);

namespace Montapacking\MontaCheckout\Test\Integration;

use Magento\Framework\Module\Manager;
use Monta\CheckoutApiWrapper\MontapackingShipping;
use Magento\Framework\App\DeploymentConfig;
//
use PHPUnit\Framework\TestCase;
use Monta\CheckoutApiWrapper\Objects\Settings;

final class IntegrationTest extends TestCase
{
    private MontapackingShipping $api;

    public function __construct()
    {
        parent::__construct();

        $settings = new Settings($_ENV['SHOP'], $_ENV['USERNAME'], $_ENV['PASSWORD'], _ENV['PICKUP_POINTS_ENABLED'], $_ENV['PICKUP_POINTS_MAX_COUNT'], $_ENV['GOOGLEAPIKEY'], $_ENV["DEFAULT_COSTS"]);

        $this->api = new MontapackingShipping($settings, 'nl-NL');

        $this->api->setAddress(
            'Papland',
            16,
            '',
            '4206L',
            'Gorinchem',
            '',
            'NL' 
        );

        $this->api->addProduct('croc', 1);
    }

//    /** @test */
    public function testGetDeliveryOptionsAndPickupPoints(): void
    {
        $results = $this->api->getShippingOptions();

        $this->assertTrue(sizeof($results['DeliveryOptions']) > 0);
        $this->assertTrue(sizeof($results['PickupOptions']) > 0);
    }
}
