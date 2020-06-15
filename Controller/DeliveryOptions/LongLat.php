<?php

namespace Montapacking\MontaCheckout\Controller\DeliveryOptions;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Locale\ResolverInterface as LocaleResolver;
use Montapacking\MontaCheckout\Controller\AbstractDeliveryOptions;

use Montapacking\MontaCheckout\Model\Config\Provider\Carrier as CarrierConfig;

use Montapacking\MontaCheckout\Api\MontapackingShipping as MontpackingApi;

class LongLat extends AbstractDeliveryOptions
{
    /** @var Session $checkoutSession */
    private $checkoutSession;

    /** @var LocaleResolver $scopeConfig */
    private $localeResolver;

    protected $_logger;

    /**
     * Services constructor.
     *
     * @param Context $context
     * @param Session $checkoutSession
     * @param CarrierConfig $carrierConfig
     */
    public function __construct(
        Context $context,
        Session $checkoutSession,
        LocaleResolver $localeResolver,
        CarrierConfig $carrierConfig,
        \Montapacking\MontaCheckout\Logger\Logger $logger
    )
    {
        $this->_logger = $logger;

        $this->checkoutSession = $checkoutSession;
        $this->localeResolver = $localeResolver;

        parent::__construct(
            $context,
            $carrierConfig
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
        $oApi = $this->generateApi($request, $language, $this->_logger);
        $has_connection = $oApi->checkConnection();

        $arr = array();

        $arr['longitude'] = $oApi->address->longitude;
        $arr['latitude'] = $oApi->address->latitude;
        $arr['language'] = $language;

        if (true === $has_connection) {
            $arr['hasconnection'] = 'true';
        } else {
            $arr['hasconnection'] = 'false';
        }

        return $this->jsonResponse($arr);
        exit;
    }
}
