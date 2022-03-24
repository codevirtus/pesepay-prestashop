<?php

if (!defined('_PS_VERSION_')) {
    exit();
}

/**
 * Order checkout page for presta shop 1.6
 *
 * @version 1.0.0
 * @since 1.0.0
 * @author Pesepay <developer@pesepay.com>
 * @copyright Pesepay <developer@pesepay.com>
 */
class PesepayPaymentModuleFrontController extends ModuleFrontController
{

    //public $ssl = true;
    public $display_column_left = false;

    /**
     * @see FrontController::initContent()
     */
    public function initContent()
    {
        parent::initContent();

        $cart = $this->context->cart;

        $cookie = $this->context->cookie;

        if ($cookie->PESEPAY_ERROR) {
            $this->context->smarty->assign('error', $cookie->PESEPAY_ERROR);
            $cookie->__unset('PESEPAY_ERROR');
        }

        /**
         * @var Pesepay $module
         */
        $module = $this->module;

        $this->context->smarty->assign(array(
            'nbProducts' => $cart->nbProducts(),
            'cust_currency' => $cart->id_currency,
            'currencies' => $module->getCurrency((int)$cart->id_currency),
            'total' => $cart->getOrderTotal(true, Cart::BOTH),
            'isoCode' => $this->context->language->iso_code,
            'this_path' => $module->getPathUri(),
            'this_path_pesepay' => $module->getPathUri(),
            'this_path_ssl' => Tools::getShopDomainSsl(true, true) . __PS_BASE_URI__ . 'modules/' . $module->name . '/'
        ));

        $this->setTemplate('payment.tpl');
    }
}