<?php

if (!defined('_PS_VERSION_')) {
    exit();
}

/**
 * Dynamically load required files
 * 
 * @version 1.0.0
 * @since 1.0.0
 * @author Pesepay <developer@pesepay.com>
 * @copyright Pesepay <developer@pesepay.com>
 */

require_once _PS_MODULE_DIR_ . 'pesepay/includes/payment.php';

/**
 * load only on Prestashop 1.7+
 */
if (PesepayPaymentUtils::isPrestaShop17()) {
    require_once _PS_MODULE_DIR_ . 'pesepay/includes/compat/payment.php';
}