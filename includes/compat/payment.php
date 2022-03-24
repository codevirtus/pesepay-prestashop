<?php

/**
 * Functions only available in prestashop 1.7+
 * 
 * @package Codevirtus\Pesepay
 * @author Pesepay <developer@pesepay.com>
 * @link http://pesepay.com
 */

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

if (!defined('_PS_VERSION_')) {
	exit();
}

/**
 * Compatibility class for functions added in Prestashop 1.7
 * 
 * @version 1.0.0
 * @since 1.0.0
 * @author Pesepay <developer@pesepay.com>
 * @copyright Pesepay <developer@pesepay.com>
 */
class PesepayPaymentCompat
{

	public static function getPaymentOption($name, $callToAction, $logo, $action)
	{
		$newOption = new PaymentOption();
		$newOption->setModuleName($name)->setCallToActionText($callToAction)->setLogo($logo)->setAction($action);

		#FIXME: might be a better way to do this
		$newOption->setAdditionalInformation('<style>img[src$="pesepay-badge.png"]{max-width:90%;}</style>');

		return $newOption;
	}
}