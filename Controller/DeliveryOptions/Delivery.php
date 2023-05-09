<?php

namespace Montapacking\MontaCheckout\Controller\DeliveryOptions;

use Exception;
use Magento\Checkout\Model\Cart;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Locale\ResolverInterface as LocaleResolver;
use Montapacking\MontaCheckout\Controller\AbstractDeliveryOptions;

use Montapacking\MontaCheckout\Helper\DeliveryHelper;
use Montapacking\MontaCheckout\Helper\PickupHelper;
use Montapacking\MontaCheckout\Logger\Logger;
use Montapacking\MontaCheckout\Model\Config\Provider\Carrier as CarrierConfig;

/**
 * Class Delivery
 *
 * @package Montapacking\MontaCheckout\Controller\DeliveryOptions
 */
class Delivery extends AbstractDeliveryOptions
{
    /** @var Session $checkoutSession */
    private $checkoutSession;

    /** @var LocaleResolver $scopeConfig */
    private $localeResolver;

    /**
     * @var \Montapacking\MontaCheckout\Logger\Logger
     */
    protected $_logger;

    /**
     * @var \Magento\Checkout\Model\Cart
     */
    public $cart;

    /**
     * @var \Montapacking\MontaCheckout\Helper\PickupHelper
     */
    protected $pickupHelper;

    /**
     * @var \Montapacking\MontaCheckout\Helper\DeliveryHelper
     */
    protected $deliveryHelper;

    /**
     * Services constructor.
     *
     * @param Context $context
     * @param Session $checkoutSession
     * @param CarrierConfig $carrierConfig
     */
    public function __construct(
        Context         $context,
        Session         $checkoutSession,
        LocaleResolver  $localeResolver,
        CarrierConfig   $carrierConfig,
        Logger          $logger,
        Cart            $cart,
        PickupHelper    $pickupHelper,
        DeliveryHelper  $deliveryHelper
    )
    {
        $this->_logger = $logger;
        $this->checkoutSession = $checkoutSession;
        $this->localeResolver = $localeResolver;
        $this->cart = $cart;
        $this->pickupHelper = $pickupHelper;
        $this->deliveryHelper = $deliveryHelper;

        parent::__construct(
            $context,
            $carrierConfig,
            $cart
        );
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     * @throws \Zend_Http_Client_Exception
     */
    public function execute()
    {
        $request = $this->getRequest();
        $language = strtoupper(strstr($this->localeResolver->getLocale(), '_', true));

        if ($language != 'NL' && $language != 'BE' && $language != 'DE') {
            $language = 'EN';
        }

        try {
            $oApi = $this->generateApi($request, $language, $this->_logger, true);

            $shippingoptions = $oApi->getShippingOptions($oApi->getOnstock());

            $shippingoptions_formatted = $this->deliveryHelper->formatShippingOptions($shippingoptions['DeliveryOptions']);
            $pickupoptions_formatted = $this->pickupHelper->formatPickupOptions($shippingoptions['PickupOptions']);

            return $this->jsonResponse([$shippingoptions_formatted, $pickupoptions_formatted]);

        } catch (Exception $e) {

            $context = ['source' => 'Montapacking Checkout'];
            $this->_logger->critical("Webshop was unable to connect to Montapacking REST api. Please contact Montapacking", ['error' => $e->getMessage()]); //phpcs:ignore
            return $this->jsonResponse(json_encode([]));
        }
    }
}
