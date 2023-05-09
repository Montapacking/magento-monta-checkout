<?php

namespace Montapacking\MontaCheckout\Helper;

use Magento\Framework\Locale\ResolverInterface as LocaleResolver;
use \DateTime;
use \IntlDateFormatter;

/**
 * Class DeliveryHelper
 *
 * @package Montapacking\MontaCheckout\Helper\DeliveryHelper
 */
class DeliveryHelper
{
    /** @var LocaleResolver $scopeConfig */
    private $localeResolver;

    /**
     * @var \Montapacking\MontaCheckout\Logger\Logger
     */
    protected $_logger;

    /**
     * Services constructor.
     *
     */
    public function __construct(
        LocaleResolver $localeResolver,
        \Montapacking\MontaCheckout\Logger\Logger $logger
    ) {
        $this->_logger = $logger;
        $this->localeResolver = $localeResolver;
    }

    /**
     * @param $frames
     *
     * @return array
     */
    public function formatShippingOptions($frames)
    {
        $items = [];

        $language = strtoupper(strstr($this->localeResolver->getLocale(), '_', true));

        if ($language != 'NL' && $language != 'BE' && $language != 'DE') {
            $language = 'EN';
        }

        $hour_string = "h";
        if ($language == 'NL') {
            $hour_string = " uur";
        }
        if ($language == 'DE') {
            $hour_string = " Uhr";
        }

        ## Currency symbol
        $curr = '€';

        if (is_array($frames) || is_object($frames)) {

            foreach ($frames as $nr => $frame) {

                foreach ($frame->options as $onr => $option) {

                    $description = [];

                    $from = null;
                    $to = null;
                    $date = null;
                    $time = null;

                    if ($frame->type == 'DeliveryDay') {
                        $from = $option->from;
                        $to = $option->to;
                        $date = date("Y-m-d", strtotime($from));
                        $time = date('H:i', strtotime($frame->from)) != date('H:i', strtotime($frame->to)) ? date('H:i', strtotime($frame->from)) . '-' . date('H:i', strtotime($frame->to)) : '';

                        if (trim($option->description)) {
                            $description[] = $option->description;
                        }
                        if (date('H:i', strtotime($from)) != '00:00') {
                            $description[] = date('H:i', strtotime($from)) . " - " . date('H:i', strtotime($to)) . $hour_string; //phpcs:ignore
                        }
                        if ($frame->code != null && trim($frame->code)) {
                            $frame->code_desc = __($frame->code);
                            $description[] = $frame->code_desc;
                        }
                    }

                    if ($frame->type == 'ShippingDay') {
                        $from = date("Y-m-d", strtotime($option->date));
                        $to = date("Y-m-d", strtotime($option->date));
                        $date = date("Y-m-d", strtotime($from));

                        if (trim($option->description)) {
                            $description[] = $option->description;
                        }

                        if ($frame->code != null && trim($frame->code)) {
                            $description[] = $frame->code;
                        }

                        $description[] = __("Ships on this date from the Netherlands");
                    }

                    if ($frame->type == 'Unknown') {

                        if (isset($option->date) && strtotime($option->date) > 0) {
                            $from = date("Y-m-d", strtotime($option->date));
                            $to = date("Y-m-d", strtotime($option->date));
                        } elseif ($option->code == 'RED_ShippingDayUnknown') {
                            $from = date('d-m-Y', time());
                            $to = $from = date('d-m-Y', time());
                        } elseif ($option->code == 'Trunkrs_ShippingDayUnknown') {
                            $from = date('d-m-Y', time());
                            $to = $from = date('d-m-Y', time());
                        }

                        if (isset($from)) {
                            $date = date("Y-m-d", strtotime($from));
                        }


                        if (trim($option->description)) {
                            $description[] = $option->description;
                        }

                        if ($frame->code != null && trim($frame->code)) {
                            $description[] = $frame->code;
                        }

                    }

                    $description = implode(" | ", $description);

                    $extras = [];
                    if (isset($option->extras) && count($option->extras) > 0) {
                        $extras = self::calculateExtras($option->extras, $curr);
                    }

                    $options = [];
                    $created_option = self::calculateOptions($frame, $option, $curr, $description, $from, $to, $extras, $hour_string); //phpcs:ignore

                    if (null !== $created_option) {

                        $options[] = $created_option;
                        if (!isset($items[$date])) {
                            $items[$date] = [];
                        }

                        $items[$date][] = (object)[
                            'code' => $frame->code,
                            'date' => $from != null && strtotime($from) > 0 ? date('d-m-Y', strtotime($from)) : "",
                            'time' => $time != null ? $time : "", //phpcs:ignore
                            'description' => $frame->description,
                            'price_currency' => $curr,
                            'options' => $options,
                        ];
                    }

                }
            }
        }

        ksort($items);
        $list = [];
        foreach ($items as $key => $values) {


            foreach ($values as $key_value => $value) {
                $list[] = $value;
            }
        }
        $items = $list;

        return $items;
    }

    public function calculateExtras($extra_values = [], $curr = '&euro;')
    {

        $extras = [];
        if (count($extra_values) > 0) {

            foreach ($extra_values as $extra) {

                $language = strtoupper(strstr($this->localeResolver->getLocale(), '_', true));

                ## Extra optie toevoegen
                $extras[] = (array)[
                    'code' => $extra->code,
                    'name' => __($extra->code),
                    'price_currency' => $curr,
                    'price_string' => $curr . ' ' . number_format($extra->price, 2, ',', ''),
                    'price_raw' => number_format($extra->price, 2),
                    'price_formatted' => number_format($extra->price, 2, ',', ''),
                ];

            }
        }

        return $extras;
    }

    public function calculateOptions($frame, $option, $curr, $description, $from, $to, $extras, $hour_string)
    {
        if ($from != null && (date("Y-m-d", strtotime($from)) == date("Y-m-d") && $frame->code != 'SameDayDelivery')) {
            return null;
        }

        $date_string = "";

        if ($from != null && strtotime($from) > 0) {
            $date_string = __(date("l", strtotime($from))) . " " . date("d", strtotime($from)) . " " . __(date("F", strtotime($from)));
        }

        $description = str_replace("PostNL Pakket", "PostNL", $description);
        $name = $option->description;

        if($option->displayName != null){
            $parts = explode("|", $description);
            $parts[0] = $option->displayName;
            $description = implode(" | ", $parts);
            
            $name = $option->displayName;
        }

        if(count($option->codes) > 2){
            $image_code = 'DEF';
        } else {
            $image_code = trim(str_replace(",", "_", implode(",", $option->codes)));
        }

        $options = (object)[
            'code' => $option->code,
            'codes' => $option->codes,
            'type' => $frame->type,
            'image' => trim(implode(",", $option->codes)),
            'image_replace' => trim($image_code),
            'optionCodes' => $option->optioncodes,
            'name' => $name,
            'description_string' => $description,
            'price_currency' => $curr,
            'price_string' => $curr . ' ' . number_format($option->price, 2, ',', ''),
            'price_raw' => number_format($option->price, 2),
            'price_formatted' => number_format($option->price, 2, ',', ''),
            'from' => $from != null && strtotime($from) > 0 ? date('H:i', strtotime($from)) : "",
            'to' => $to != null && strtotime($to) > 0 ? date('H:i', strtotime($to)) : "",
            'date' => $from != null && strtotime($from) > 0 ? date("d-m-Y", strtotime($from)) : "",
            'date_string' => $date_string,
            'date_from_to' => $from != null && strtotime($from) > 0 ? date('H:i', strtotime($from)) . "-" . date('H:i', strtotime($to)) : "",
            'date_from_to_formatted' => $from != null && strtotime($from) > 0 ? date('H:i', strtotime($from)) . " - " . date('H:i', strtotime($to)) . $hour_string : "", //phpcs:ignore
            'extras' => $extras,
            'isPreferred' => $option->isPreferred,
            'discount_percentage' => $option->discountPercentage
        ];

        return $options;
    }
}
