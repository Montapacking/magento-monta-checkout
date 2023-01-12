<?php

namespace Montapacking\MontaCheckout\Plugin\Quote\Model\Quote\Address\Total;

use Magento\Quote\Model\Quote;
use Magento\Quote\Api\Data\ShippingAssignmentInterface as ShippingAssignmentApi;
use Magento\Quote\Model\Quote\Address\Total as QuoteAddressTotal;
use Montapacking\MontaCheckout\Model\Config\Provider\Carrier;

class Shipping
{
    protected $_checkoutSession;

    public function __construct(
        \Magento\Checkout\Model\Session             $checkoutSession
    ) {
        $this->_checkoutSession = $checkoutSession;
    }

    /**
     * @param                       $subject
     * @param                       $result
     * @param Quote                 $quote
     * @param ShippingAssignmentApi $shippingAssignment
     * @param QuoteAddressTotal     $total
     *
     * @return void|mixed
     */
    // @codingStandardsIgnoreLine
    public function afterCollect($subject, $result, Quote $quote, ShippingAssignmentApi $shippingAssignment, QuoteAddressTotal $total)
    {
        $shipping = $shippingAssignment->getShipping();
        $address = $shipping->getAddress();
        $rates = $address->getAllShippingRates();

        $fee = \Magento\Framework\App\ObjectManager::getInstance()->get(\Magento\Framework\App\Config\ScopeConfigInterface::class)->getValue('carriers/montapacking/price',\Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        if (!$rates) {
            return $result;
        }

        if (empty($rates)) {
            return $result;
        }

        $array = (array) $rates;


        $deliveryOption = $this->getDeliveryOption($address);

        if (!$deliveryOption) {
            return $result;
        }

        $method = $shipping->getMethod();

        $deliveryOptionType = $deliveryOption->type;
        $deliveryOptionDetails = $deliveryOption->details[0];
        $deliveryOptionAdditionalInfo = $deliveryOption->additional_info[0];

        if ($deliveryOptionType != 'pickup' && $deliveryOptionType != 'delivery') {
            return $result;
        }

        if ($deliveryOptionType == 'pickup') {

            $fee = $deliveryOptionAdditionalInfo->total_price;
            $method_title = $deliveryOptionAdditionalInfo->company;

            $desc = explode("|", $deliveryOptionAdditionalInfo->description);
            $desc = $desc[0];
        }

        if ($deliveryOptionType == 'delivery') {
            $fee = $deliveryOptionAdditionalInfo->total_price;
            $method_title = $deliveryOptionAdditionalInfo->name;

            $desc = [];
            if (trim($deliveryOptionAdditionalInfo->date)) {
                $desc[] = $deliveryOptionAdditionalInfo->date;
            }

            if (trim($deliveryOptionAdditionalInfo->time)) {
                $desc[] = $deliveryOptionAdditionalInfo->time;
            }

            // extra options
            if (isset($deliveryOptionDetails->options)) {
                foreach ($deliveryOptionDetails->options as $value) {
                    $desc[] = $value;
                }
            }

            $desc = implode(" | ", $desc);
        }

        $this->adjustTotals($method_title, $subject->getCode(), $address, $total, $fee, $desc);
    }

    /**
     * @param $address
     *
     * @return mixed|null
     */
    private function getDeliveryOption($address)
    {
        $option = $address->getMontapackingMontacheckoutData();

        if (!$option) {
            return null;
        }

        $option = json_decode($option);

        return $option;
    }

    private function adjustTotals($name, $code, $address, $total, $fee, $description)
    {
        $total->setTotalAmount($code, $fee);
        $total->setBaseTotalAmount($code, $fee);
        $total->setBaseShippingAmount($fee);
        $total->setShippingAmount($fee);
        $total->setShippingDescription($name . ' - ' . $description);
        $total->setShippingMethodTitle($name . ' - ' . $description);

        $address->setShippingDescription($name . ' - ' . $description);
    }

    private function getFreeShipping() 
    {
        return $this->_checkoutSession->getFreeShipping();
    }
}
