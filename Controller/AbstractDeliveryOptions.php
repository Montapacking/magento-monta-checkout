<?php

namespace Montapacking\MontaCheckout\Controller;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\RequestInterface;
use Monta\CheckoutApiWrapper\Objects\Settings;
use Montapacking\MontaCheckout\Model\Config\Provider\Carrier as CarrierConfig;
//use Monta\CheckoutApiWrapper\Objects\Settings;
use Monta\CheckoutApiWrapper\MontapackingShipping as MontpackingApi;

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
        Context                      $context,
        CarrierConfig                $carrierConfig,
        \Magento\Checkout\Model\Cart $cart
    )
    {
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
     * @param null $code
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
            json_encode($data)
        );
    }

    public function generateApi(RequestInterface $request, $language, $logger = null, $use_googlekey = false)
    {
//        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
//        $directory = $objectManager->get('\Magento\Framework\Filesystem\DirectoryList');
//        $rootDir = $directory->getRoot();
//
//        require_once $directory->getRoot() . '/app/code/Montapacking/MontaCheckout/vendor/monta/checkout-api-wrapper/src/MontapackingShipping.php';
//        require_once $directory->getRoot() . '/app/code/Montapacking/MontaCheckout/vendor/monta/checkout-api-wrapper/src/Objects/Address.php';
//        require_once $directory->getRoot() . '/app/code/Montapacking/MontaCheckout/vendor/monta/checkout-api-wrapper/src/Objects/OpeningTime.php';
//        require_once $directory->getRoot() . '/app/code/Montapacking/MontaCheckout/vendor/monta/checkout-api-wrapper/src/Objects/Option.php';
//        require_once $directory->getRoot() . '/app/code/Montapacking/MontaCheckout/vendor/monta/checkout-api-wrapper/src/Objects/Order.php';
//        require_once $directory->getRoot() . '/app/code/Montapacking/MontaCheckout/vendor/monta/checkout-api-wrapper/src/Objects/PickupPoint.php';
//        require_once $directory->getRoot() . '/app/code/Montapacking/MontaCheckout/vendor/monta/checkout-api-wrapper/src/Objects/Product.php';
//        require_once $directory->getRoot() . '/app/code/Montapacking/MontaCheckout/vendor/monta/checkout-api-wrapper/src/Objects/Settings.php';
//        require_once $directory->getRoot() . '/app/code/Montapacking/MontaCheckout/vendor/monta/checkout-api-wrapper/src/Objects/Shipper.php';
//        require_once $directory->getRoot() . '/app/code/Montapacking/MontaCheckout/vendor/monta/checkout-api-wrapper/src/Objects/ShippingOption.php';
//        require_once $directory->getRoot() . '/app/code/Montapacking/MontaCheckout/vendor/monta/checkout-api-wrapper/src/Objects/TimeFrame.php';

        if($request->getParam('street') != null && is_array($request->getParam('street')) && count($request->getParam('street')) > 1){
            $street =  trim(implode(" ", $request->getParam('street')));
        } else if ($request->getParam('street') != null) {
            $street = trim($request->getParam('street'));
        } else {
            $street = "";
        }
        $postcode = $request->getParam('postcode') ? trim($request->getParam('postcode')) : "";
        $city = $request->getParam('city') ? trim($request->getParam('city')) : "";
        $country = $request->getParam('country') ? trim($request->getParam('country')) : "";

        $housenumber = $request->getParam('housenumber') ? trim($request->getParam('housenumber')) : "";
        $housenumberaddition = $request->getParam('housenumberaddition') ? trim($request->getParam('housenumberaddition')) : "";
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
        $disabledeliverydays = $this->getCarrierConfig()->getDisableDeliveryDays();

        /**
         * Retrieve Order Information
         */
        $cart = $this->getCart();

        /**
         * Todo: Fix to make dynamic from Magento settings later
         */
        $settings = new Settings(
            $webshop,
            $username,
            $password,
            true,
            4,
            $googleapikey,
            200
        );

        $oApi = new MontpackingApi($settings, $language);
        $oApi->setAddress($street, $housenumber, $housenumberaddition, $postcode, $city, $state, $country);

        $priceIncl = $cart->getQuote()->getSubtotal();
        $priceExcl = $cart->getQuote()->getSubtotal();

        if ($cart->getQuote()->getSubtotalInclTax() > 0) {
            $priceIncl = $cart->getQuote()->getSubtotalInclTax();
            $priceIncl = $cart->getQuote()->getSubtotalInclTax();
        } else if ($cart->getQuote()->getShippingAddress()->getSubtotalInclTax() > 0) {
            $priceIncl = $cart->getQuote()->getShippingAddress()->getSubtotalInclTax();
            $priceExcl = $cart->getQuote()->getShippingAddress()->getSubtotal();
        }

        $oApi->setOrder($priceIncl, $priceExcl); //phpcs:ignore

        $items = $cart->getQuote()->getAllVisibleItems();

        $bAllProductsAvailable = true;


        foreach($items as $item) {
            $oApi->addProduct(
                $item->getSku(),
                $item->getQty(),
                $item->getData('length') ?: 0,
                $item->getData('width') ?: 0,
                $item->getData('weight') ?: 0
            );
        }

//        foreach ($items as $item) {
//
//            if ($leadingstockmontapacking) {
//
////                $oApi->addProduct($item->getSku(), $item->getQty(), $item->getData('length'), $item->getData('width'), $item->getData('weight'));
//
//                if (!$disabledeliverydays) {
//                    $oApi->addProduct($item->getSku(), $item->getQty(), $item->getData('length'), $item->getData('width'), $item->getData('weight'));
//
//                    // we let our api calculate the stock with the added products, so we set the stock on false
////                    $bAllProductsAvailable = true;
//
//                    if (false === $oApi->checkStock($item->getSku())) {
//                        $bAllProductsAvailable = false;
//                        break;
//                    }
//
//                }
//
//            } else {
//                $stockItem = $item->getProduct()->getExtensionAttributes()->getStockItem();
//
//                //print $stockItem->getQty()."-".$item->getQty();
//                //exit;
//                //echo "<pre>";print_r($item->debug());
//
//                if ($stockItem->getQty() <= 0 || $stockItem->getQty() < $item->getQty()) {
//
//                    $bAllProductsAvailable = false;
//                    break;
//                }
//            }
//        }

//        if (false === $bAllProductsAvailable || $disabledeliverydays) {
//            $oApi->setOnstock(false);
//        }

//        $frames = $oApi->getShippingOptions($bAllProductsAvailable, false, false, false, false);
        $frames = $oApi->getShippingOptions();

        return $frames;
    }
}
