<?php

namespace Montapacking\MontaCheckout\Controller\DeliveryOptions;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Locale\ResolverInterface as LocaleResolver;
use Montapacking\MontaCheckout\Controller\AbstractDeliveryOptions;

use Montapacking\MontaCheckout\Model\Config\Provider\Carrier as CarrierConfig;

use Montapacking\MontaCheckout\Api\MontapackingShipping as MontpackingApi;

class Delivery extends AbstractDeliveryOptions
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

        $shippingoptions = $oApi->getShippingOptions($oApi->getOnstock());

        $shippingoptions_formatted = $this->formatShippingOptions($shippingoptions, $language);


        if (isset($_GET['log'])) {
            echo "<pre>";
            $json_encode = json_encode($shippingoptions_formatted);
            var_dump(json_decode($json_encode, true));
            echo "</pre>";

            exit;
        }

        return $this->jsonResponse($shippingoptions_formatted);
        exit;

    }

    public function formatShippingOptions($frames, $language)
    {
        $items = array();

        $hour_string = 'h';
        if ($language == 'NL') {
            setlocale(LC_TIME, "nl_NL");
            $hour_string = " uur";
        }

        ## Currency symbol
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


                            if ($language == 'NL' || $language == 'BE') {
                                if ($frame->code == 'SameDayDelivery') {
                                    $frame->code_desc = 'Zelfde dag levering';

                                }
                                if ($frame->code == 'SaturdayDelivery') {
                                    $frame->code_desc = 'Zaterdag levering';
                                }

                                if ($frame->code == 'EveningDelivery') {
                                    $frame->code_desc = 'In avond afleveren';
                                }

                            } else {
                                if ($frame->code == 'SameDayDelivery') {
                                    $frame->code_desc = 'Same day delivery';

                                }
                                if ($frame->code == 'SaturdayDelivery') {
                                    $frame->code_desc = 'Saturday delivery';
                                }

                                if ($frame->code == 'EveningDelivery') {
                                    $frame->code_desc = 'Evening Delivery';
                                }
                            }

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
