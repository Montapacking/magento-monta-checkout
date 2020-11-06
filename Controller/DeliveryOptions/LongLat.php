<?php

namespace Montapacking\MontaCheckout\Controller\DeliveryOptions;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Locale\ResolverInterface as LocaleResolver;
use Montapacking\MontaCheckout\Controller\AbstractDeliveryOptions;

use Montapacking\MontaCheckout\Model\Config\Provider\Carrier as CarrierConfig;

use Montapacking\MontaCheckout\Api\MontapackingShipping as MontpackingApi;

/**
 * Class LongLat
 *
 * @package Montapacking\MontaCheckout\Controller\DeliveryOptions
 */
class LongLat extends AbstractDeliveryOptions
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
     * Services constructor.
     *
     * @param Context       $context
     * @param Session       $checkoutSession
     * @param CarrierConfig $carrierConfig
     */
    public function __construct(
        Context $context,
        Session $checkoutSession,
        LocaleResolver $localeResolver,
        CarrierConfig $carrierConfig,
        \Montapacking\MontaCheckout\Logger\Logger $logger,
        \Magento\Checkout\Model\Cart $cart
    ) {
        $this->_logger = $logger;

        $this->checkoutSession = $checkoutSession;
        $this->localeResolver = $localeResolver;
        $this->cart = $cart;

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

        if ($language != 'NL' && $language != 'BE') {
            $language = 'EN';
        }

        try {

            $oApi = $this->generateApi($request, $language, $this->_logger);
            $has_connection = $oApi->checkConnection();

            $arr = [];

            $arr['longitude'] = $oApi->address->longitude;
            $arr['latitude'] = $oApi->address->latitude;
            $arr['language'] = $language;
            $arr['googleapikey'] = $this->getCarrierConfig()->getGoogleApiKey();

            if (true === $has_connection) {
                $arr['hasconnection'] = 'true';
            } else {
                $arr['hasconnection'] = 'false';
            }

        } catch (Exception $e) {

            $arr = [];
            $arr['longitude'] = 0;
            $arr['latitude'] = 0;
            $arr['language'] = $language;
            $arr['hasconnection'] = 'false';
            $arr['googleapikey'] = $this->getCarrierConfig()->getGoogleApiKey();


            $context = ['source' => 'Montapacking Checkout'];
            $this->_logger->critical("Webshop was unable to connect to Montapacking REST api. Please contact Montapacking", $context); //phpcs:ignore

        }

        return $this->jsonResponse($arr);
    }
}
