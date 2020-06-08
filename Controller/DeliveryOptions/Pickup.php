<?php

namespace Montapacking\MontaCheckout\Controller\DeliveryOptions;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Locale\ResolverInterface as LocaleResolver;
use Montapacking\MontaCheckout\Controller\AbstractDeliveryOptions;

use Montapacking\MontaCheckout\Model\Config\Provider\Carrier as CarrierConfig;

use Montapacking\MontaCheckout\Api\MontapackingShipping as MontpackingApi;

class Pickup extends AbstractDeliveryOptions
{
    /** @var Session $checkoutSession */
    private $checkoutSession;

    /** @var LocaleResolver $scopeConfig */
    private $localeResolver;

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
        CarrierConfig $carrierConfig
    )
    {


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

        $oApi = $this->generateApi($request, $language);

        if ($language != 'NL' && $language != 'BE') {
            $language = 'EN';
        }

        $pickupoptions = $oApi->getPickupOptions($oApi->getOnstock());

        $pickupoptions_formatted = $this->formatPickupOptions($pickupoptions, $language);

        if (isset($_GET['log'])) {
            echo "<pre>";
            $json_encode = json_encode($pickupoptions_formatted);
            var_dump(json_decode($json_encode, true));
            echo "</pre>";

            exit;
        }
        return $this->jsonResponse($pickupoptions_formatted);
        exit;

    }

    public function formatPickupOptions($frames, $language)
    {
        $items = array();

        $hour_string = 'h';
        $from_string = 'from ';
        if ($language == 'NL') {
            setlocale(LC_TIME, "nl_NL");
            $hour_string = " uur";
            $from_string = "v.a. ";
        }

        ## Currency symbol
        $curr = '€';

        $marker_id = 0;
        ## Check of er meerdere timeframes zijn, wanneer maar één dan enkel shipper keuze zonder datum/tijd
        if (is_array($frames) || is_object($frames)) {

            foreach ($frames as $nr_temp => $frame) {
                $nr = $nr_temp + 1;

                ## Alleen als er van en tot tijd bekend is (skipped nu DPD en UPS)
                if ($frame->from != '' && $frame->to != '') {

                    ## Loop trough options
                    $selected = null;

                    ## Lowest price
                    $lowest = 9999999;

                    ## Shipper opties ophalen
                    $options = null;
                    foreach ($frame->options as $onr => $option) {
                        $from = $option->from;
                        $to = $option->to;

                        ## Check of maximale besteltijd voorbij is
                        if (time() < strtotime($option->date) && $selected == null) {
                            $selected = $option;
                        }


                        ## Shipper optie toevoegen

                        $description = array();
                        $description_storelocator = array();
                        if (trim($option->description)) {

                            $description[] = $option->description;
                            $description_storelocator[] = $option->description;
                        }

                        if (trim($frame->description->DistanceMeters)) {
                            $distance = round($frame->description->DistanceMeters / 1000, 2);
                            $description[] = str_replace(".", ",", $distance) . " km";
                        }

                        if (date('H:i', strtotime($from)) != '00:00') {
                            // $description[] = date('d-m-Y', strtotime($frame->from))." ".$from_string . date('H:i', strtotime($from)) . $hour_string;
                        }

                        $description = implode(" | ", $description);

                        $marker_id++;


                        $options[$onr] = (object)[
                            'marker_id' => $marker_id,
                            'code' => $option->code,
                            'codes' => $option->codes,
                            'image' => $option->codes[0],
                            'optionCodes' =>  json_encode((array)  $option->optioncodes),
                            'optionsWithValue' =>  json_encode((array)  $option->optionsWithValue),
                            'name' => $option->description,
                            'description_string' => $description,
                            'description_string_storelocator' => $description_storelocator,
                            'price_currency' => $curr,
                            'price_string' => $curr . ' ' . number_format($option->price, 2, ',', ''),
                            'price_raw' => number_format($option->price, 2),
                            'price_formatted' => number_format($option->price, 2, ',', ''),
                            'from' => date('H:i', strtotime($from)),
                            'to' => date('H:i', strtotime($to)),
                            'date' => date("d-m-Y", strtotime($from)),
                            'date_string' => strftime('%A %e %B %Y', strtotime($from)),
                            'date_from_to' => date('H:i', strtotime($from)) . "-" . date('H:i', strtotime($to)),
                            'date_from_to_formatted' => date('H:i', strtotime($from)) . " - " . date('H:i', strtotime($to)) . $hour_string

                        ];


                        ## Check if we have a lower price
                        if ($option->price < $lowest) {
                            $lowest = $option->price;
                        }

                    }


                    if ($language == 'NL' || $language == 'BE') {
                        $arr = array();


                        foreach ($frame->description->OpeningTimes as $key => $time) {

                            if (isset($time->Day)) {

                                $days = array();
                                $days['Monday'] = 'Maandag';
                                $days['Tuesday'] = 'Dinsdag';
                                $days['Wednesday'] = 'Woensdag';
                                $days['Thursday'] = 'Donderdag';
                                $days['Friday'] = 'Vrijdag';
                                $days['Saturday'] = 'Zaterdag';
                                $days['Sunday'] = 'Zondag';

                                $days_to_use = $days[$time->Day];

                                //$arr[$key]->Day =$days_to_use;

                                $obj = (object)[
                                    'Day' => $days_to_use,
                                    'OpeningTimes' => $time->OpeningTimes,
                                    'TimeNotation' => ' uur'
                                ];

                                $arr[$key] = $obj;
                            }

                        }

                        $frame->description->OpeningTimes = $arr;
                    } else {
                        foreach ($frame->description->OpeningTimes as $key => $time) {

                            if (isset($time->Day)) {
                                $obj = (object)[
                                    'Day' => $time->Day,
                                    'OpeningTimes' => $time->OpeningTimes,
                                    'TimeNotation' => 'h'
                                ];

                                $arr[$key] = $obj;
                            }

                        }
                        $frame->description->OpeningTimes = $arr;
                    }

                    ## Check of er een prijs is
                    if ($options !== null) {

                        $items[$nr] = (object)[
                            'code' => $frame->code,
                            'date' => date('d-m-Y', strtotime($frame->from)),
                            'time' => date('H:i', strtotime($frame->from)),
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

                    ## Shipper opties ophalen
                    $options = null;
                    foreach ($frame->options as $onr => $option) {

                        ## Check of maximale besteltijd voorbij is
                        if (time() < strtotime($option->date) && $selected == null) {
                            $selected = $option;
                        }


                        ## Shipper optie toevoegen

                        $description = array();
                        $description_storelocator = array();
                        if (trim($option->description)) {
                            $description[] = $option->description;
                            $description_storelocator[] = $option->description;
                        }

                        if (trim($frame->description->DistanceMeters)) {
                            $distance = round($frame->description->DistanceMeters / 1000, 2);
                            $description[] = str_replace(".", ",", $distance) . " km";
                        }


                        $description = implode(" | ", $description);

                        $marker_id++;

                        $options[$onr] = (object)[
                            'marker_id' => $marker_id,
                            'code' => $option->code,
                            'codes' => $option->codes,
                            'image' => $option->codes[0],
                            'optionCodes' => $option->optioncodes,
                            'name' => $option->description,
                            'description_string' => $description,
                            'description_string_storelocator' => $description_storelocator,
                            'price_currency' => $curr,
                            'price_string' => $curr . ' ' . number_format($option->price, 2, ',', ''),
                            'price_raw' => number_format($option->price, 2),
                            'price_formatted' => number_format($option->price, 2, ',', ''),
                            'from' => null,
                            'to' => null,
                            'date' => null,
                            'date_string' => null,
                            'date_from_to' => null,
                            'date_from_to_formatted' => null

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
                            'date' => null,
                            'time' => null,
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
        return $items;
    }
}
