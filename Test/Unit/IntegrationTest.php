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

    private string $monta_origin;
    private string $monta_username;
    private string $monta_password;
    private bool $monta_enable_pickup_points;
    private int $monta_max_pickup_points;
    private string $monta_google_api_key;
    private float $monta_default_costs;

    public function __construct()
    {
        parent::__construct();
        
        $this->monta_origin = (string) $_ENV['MONTA_ORIGIN'];
        $this->monta_username = (string) $_ENV['MONTA_USERNAME'];
        $this->monta_password = (string) $_ENV['MONTA_PASSWORD'];
        $this->monta_enable_pickup_points = (bool) $_ENV['MONTA_ENABLE_PICKUP_POINTS'];
        $this->monta_max_pickup_points = (int) $_ENV['MONTA_MAX_PICKUP_POINTS'];
        $this->monta_google_api_key = (string) $_ENV['MONTA_GOOGLE_API_KEY'];
        $this->monta_default_costs = (float) $_ENV['MONTA_DEFAULT_COSTS'];

        $settings = new Settings(
            $this->monta_origin,
            $this->monta_username,
            $this->monta_password,
            $this->monta_enable_pickup_points,
            $this->monta_max_pickup_points,
            $this->monta_google_api_key,
            $this->monta_default_costs);

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
