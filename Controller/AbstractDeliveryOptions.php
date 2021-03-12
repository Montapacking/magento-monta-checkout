<?php

namespace Montapacking\MontaCheckout\Controller;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\Action;

use Montapacking\MontaCheckout\Model\Config\Provider\Carrier as CarrierConfig;
use Montapacking\MontaCheckout\Api\MontapackingShipping as MontpackingApi;

abstract class AbstractDeliveryOptions extends Action
{
    /** @var $carrierConfig CarrierConfig */
    private $carrierConfig;

    public $cart;

    /**
     * AbstractDeliveryOptions constructor.
     *
     * @param Context $context
     */
    public function __construct(
        Context $context,
        CarrierConfig $carrierConfig,
        \Magento\Checkout\Model\Cart $cart
    ) {
        $this->carrierConfig = $carrierConfig;

        $this->cart = $cart;

        parent::__construct(
            $context
        );
    }

    /**
     * @return CarrierConfig
     */
    public function getCarrierConfig()
    {
        return $this->carrierConfig;
    }

    public function getCart()
    {
        return $this->cart;
    }

    /**
     * @param string $data
     * @param null   $code
     *
     * @return mixed
     */
    public function jsonResponse($data = '', $code = null)
    {
        $response = $this->getResponse();

        if ($code !== null) {
            $response->setStatusCode($code);
        }

        return $response->representJson(
            \Zend_Json::encode($data)
        );
    }

    public function generateApi($request, $language, $logger = null, $use_googlekey = false)
    {

        $street = $request->getParam('street') ? trim(implode(" ", $request->getParam('street'))) : "";
        $postcode = $request->getParam('postcode') ? trim($request->getParam('postcode')) : "";
        $city = $request->getParam('city') ? trim($request->getParam('city')) : "";
        $country = $request->getParam('country') ? trim($request->getParam('country')) : "";

        $housenumber =  $request->getParam('housenumber') ? trim($request->getParam('housenumber')) : "";
        $housenumberaddition =  $request->getParam('housenumberaddition') ? trim($request->getParam('housenumberaddition')) : "";
        $state = '';

        $postcode = str_replace(" ", "", $postcode);

        // check is ZIPCODE valid for dutch customers
        if ($country == 'NL') {
            if (!preg_match("/^\W*[1-9]{1}[0-9]{3}\W*[a-zA-Z]{2}\W*$/", $postcode)) {
                $postcode = '';
            }
        }

        if ($country == 'BE') {
            if (!preg_match('~\A[1-9]\d{3}\z~', $postcode)) {
                $postcode = '';
            }
        }

        /**
         * Configs From Admin
         */

        $webshop = $this->getCarrierConfig()->getWebshop();
        $username = $this->getCarrierConfig()->getUserName();
        $password = $this->getCarrierConfig()->getPassword();

        $googleapikey = null;
        if ($use_googlekey) {
            $googleapikey = $this->getCarrierConfig()->getGoogleApiKey();
        }

        $leadingstockmontapacking = $this->getCarrierConfig()->getLeadingStockMontapacking();

        /**
         * Retrieve Order Information
         */

        //$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $cart = $this->getCart();

        $oApi = new MontpackingApi($webshop, $username, $password, $googleapikey, $language);
        $oApi->setLogger($logger);
        $oApi->setCarrierConfig($this->getCarrierConfig());
        $oApi->setAddress($street, $housenumber, $housenumberaddition, $postcode, $city, $state, $country);
        $oApi->setOrder($cart->getQuote()->getSubtotalInclTax() > 0 ? $cart->getQuote()->getSubtotalInclTax() : $cart->getQuote()->getSubtotal(), $cart->getQuote()->getSubtotal()); //phpcs:ignore

        $items = $cart->getQuote()->getAllVisibleItems();


        $bAllProductsAvailable = true;
        foreach ($items as $item) {

            if ($leadingstockmontapacking) {
                $oApi->addProduct($item->getSku(), $item->getQty(), $item->getData('length'), $item->getData('width'), $item->getData('weight')); //phpcs:ignore

                if (false === $oApi->checkStock($item->getSku())) {
                    $bAllProductsAvailable = false;
                    break;
                }
            } else {
                $stockItem = $item->getProduct()->getExtensionAttributes()->getStockItem();
                if ($stockItem->getQty() <= 0) {
                    $bAllProductsAvailable = false;
                    break;
                }
            }

        }

        if (false === $bAllProductsAvailable) {
            $oApi->setOnstock(false);
        }
        return $oApi;
    }
}
