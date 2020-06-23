<?php

namespace Montapacking\MontaCheckout\Controller\DeliveryOptions;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Locale\ResolverInterface as LocaleResolver;
use Montapacking\MontaCheckout\Controller\AbstractDeliveryOptions;

use Montapacking\MontaCheckout\Model\Config\Provider\Carrier as CarrierConfig;

use Montapacking\MontaCheckout\Api\MontapackingShipping as MontpackingApi;

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
    private $cart;

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
    )
    {
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

            $shippingoptions = $oApi->getShippingOptions($oApi->getOnstock());

            $shippingoptions_formatted = $this->formatShippingOptions($shippingoptions);


            /* turned off
            if (isset($_GET['log'])) {

                print $language."<br>";
                echo "<pre>";
                $json_encode = json_encode($shippingoptions_formatted);
                var_dump(json_decode($json_encode, true));
                echo "</pre>";
                exit;
            }
            */

            return $this->jsonResponse($shippingoptions_formatted);

        } catch (Exception $e) {

            $context = array('source' => 'Montapacking Checkout');
            $this->_logger->critical("Webshop was unable to connect to Montapacking REST api. Please contact Montapacking", $context);
            return $this->jsonResponse(array());
        }


    }

    /**
     * @param $frames
     *
     * @return array
     */
    public function formatShippingOptions($frames)
    {
        $items = array();

        $language = strtoupper(strstr($this->localeResolver->getLocale(), '_', true));
        if ($language == 'NL') {
            setlocale(LC_TIME, "nl_NL");
        }

        $hour_string = __("hour");
        $curr = '€';

        ## Check of er meerdere timeframes zijn, wanneer maar één dan enkel shipper keuze zonder datum/tijd
        if (is_array($frames) || is_object($frames)) {

            foreach ($frames as $nr => $frame) {

                ## Alleen als er van en tot tijd bekend is (skipped nu DPD en UPS)
                if ($frame->from != '' && $frame->to != '') {

                    ## Loop trough options
                    $selected = null;

                    ## Lowest price
                    $lowest = 9999999;


                    ## Shipper opties ophalen
                    $options = null;
                    $option_counter = -1;
                    foreach ($frame->options as $onr => $option) {
                        $from = $option->from;
                        $to = $option->to;

                        ## Check of maximale besteltijd voorbij is
                        if (time() < strtotime($option->date) && $selected == null) {
                            $selected = $option;
                        }

                        $extras = array();
                        if (isset($option->extras) && count($option->extras) > 0) {

                            foreach ($option->extras as $extra) {

                                ## Extra optie toevoegen
                                $extras[] = (array)[
                                    'code' => $extra->code,
                                    'name' => $extra->name,
                                    'price_currency' => $curr,
                                    'price_string' => $curr . ' ' . number_format($extra->price, 2, ',', ''),
                                    'price_raw' => number_format($extra->price, 2),
                                    'price_formatted' => number_format($extra->price, 2, ',', ''),
                                ];

                            }

                        }

                        ## Shipper optie toevoegen

                        $description = array();
                        if (trim($option->description)) {
                            $description[] = $option->description;
                        }
                        if (date('H:i', strtotime($from)) != '00:00') {
                            $description[] = date('H:i', strtotime($from)) . " - " . date('H:i', strtotime($to)) . $hour_string;
                        }


                        if (trim($frame->code) && null !== $frame->code) {

                            $frame->code_desc = __($frame->code);
                            $description[] = $frame->code_desc;
                        }

                        $description = implode(" | ", $description);

                        $option_counter++;
                        $options[$option_counter] = (object)[
                            'code' => $option->code,
                            'codes' => $option->codes,
                            'image' => $option->codes[0],
                            'optionCodes' => $option->optioncodes,
                            'name' => $option->description,
                            'description_string' => $description,
                            'price_currency' => $curr,
                            'price_string' => $curr . ' ' . number_format($option->price, 2, ',', ''),
                            'price_raw' => number_format($option->price, 2),
                            'price_formatted' => number_format($option->price, 2, ',', ''),
                            'from' => date('H:i', strtotime($from)),
                            'to' => date('H:i', strtotime($to)),
                            'date' => date("d-m-Y", strtotime($from)),
                            'date_string' => strftime('%A %e %B %Y', strtotime($from)),
                            'date_from_to' => date('H:i', strtotime($from)) . "-" . date('H:i', strtotime($to)),
                            'date_from_to_formatted' => date('H:i', strtotime($from)) . " - " . date('H:i', strtotime($to)) . $hour_string,
                            'extras' => $extras,
                        ];

                        ## Check if we have a lower price
                        if ($option->price < $lowest) {
                            $lowest = $option->price;
                        }

                    }


                    ## Check of er een prijs is
                    if ($options !== null) {

                        $items[$nr] = (object)[
                            'code' => $frame->code,
                            'date' => date('d-m-Y', strtotime($frame->from)),
                            'time' => (date('H:i', strtotime($frame->from)) != date('H:i', strtotime($frame->to))) ? date('H:i', strtotime($frame->from)) . '-' . date('H:i', strtotime($frame->to)) : '',
                            'description' => $frame->description,
                            'price_currency' => $curr,
                            'price_string' => $curr . ' ' . number_format($lowest, 2, ',', ''),
                            'price_raw' => number_format($lowest, 2),
                            'price_formatted' => number_format($lowest, 2, ',', ''),
                            'options' => $options
                        ];

                    }

                } else {

                    ## Loop trough options
                    $selected = null;

                    ## Lowest price
                    $lowest = 9999999;

                    $shippers_found = false;
                    $frame_options = $frame->options;
                    foreach ($frame_options as $nr => $option) {
                        if ($option->code != 'TBQ_ShippingDayUnknown') {
                            $shippers_found = true;
                        }
                    }

                    ## Shipper opties ophalen

                    $shippers_found = false;
                    $frame_options = $frame->options;
                    foreach ($frame_options as $nr => $option) {
                        if ($option->code != 'TBQ_ShippingDayUnknown') {
                            $shippers_found = true;
                        }
                    }

                    $options = null;
                    $option_counter = -1;
                    foreach ($frame->options as $onr => $option) {

                        $allow = true;
                        if ($option->code == 'TBQ_ShippingDayUnknown' && true === $shippers_found) {
                            $allow = false;
                        }

                        if (true === $allow) {

                            ## Check of maximale besteltijd voorbij is
                            if (time() < strtotime($option->date) && $selected == null) {
                                $selected = $option;
                            }

                            $extras = array();
                            if (isset($option->extras) && count($option->extras) > 0) {

                                foreach ($option->extras as $extra) {

                                    ## Extra optie toevoegen
                                    $extras[] = (array)[
                                        'code' => $extra->code,
                                        'name' => $extra->name,
                                        'price_currency' => $curr,
                                        'price_string' => $curr . ' ' . number_format($extra->price, 2, ',', ''),
                                        'price_raw' => number_format($extra->price, 2),
                                        'price_formatted' => number_format($extra->price, 2, ',', ''),
                                    ];

                                }

                            }

                            ## Shipper optie toevoegen

                            $description = array();
                            if (trim($option->description)) {
                                $description[] = $option->description;
                            }

                            if (trim($frame->code) && null !== $frame->code) {
                                $description[] = $frame->code;
                            }

                            $description = implode(" | ", $description);
                            $option_counter++;
                            $options[$option_counter] = (object)[
                                'code' => $option->code,
                                'codes' => $option->codes,
                                'image' => $option->codes[0],
                                'optionCodes' => $option->optioncodes,
                                'name' => $option->description,
                                'description_string' => $description,
                                'price_currency' => $curr,
                                'price_string' => $curr . ' ' . number_format($option->price, 2, ',', ''),
                                'price_raw' => number_format($option->price, 2),
                                'price_formatted' => number_format($option->price, 2, ',', ''),
                                'from' => '',
                                'to' => '',
                                'date' => '',
                                'date_string' => '',
                                'date_from_to' => '',
                                'date_from_to_formatted' => '',
                                'extras' => $extras,
                            ];

                            ## Check if we have a lower price
                            if ($option->price < $lowest) {
                                $lowest = $option->price;
                            }

                        }


                        ## Check of er een prijs is
                        if ($options !== null) {

                            $items[1] = (object)[
                                'code' => 'ShippingDayUnknown',
                                'date' => '',
                                'time' => '',
                                'description' => $frame->description,
                                'price_currency' => $curr,
                                'price_string' => $curr . ' ' . number_format($lowest, 2, ',', ''),
                                'price_raw' => number_format($lowest, 2),
                                'price_formatted' => number_format($lowest, 2, ',', ''),
                                'options' => $options
                            ];

                        }

                    }
                }
            }

        }
        return $items;
    }

}
