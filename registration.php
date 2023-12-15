<?php
/**
 * Monta B.V.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category  Monta
 * @package   Montapacking_MontaCheckout
 * @copyright 2020 Monta B.V.
 */

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__FILE__));
$dotenv->load();

\Magento\Framework\Component\ComponentRegistrar::register(
    \Magento\Framework\Component\ComponentRegistrar::MODULE,
    'Montapacking_MontaCheckout',
    __DIR__
);
