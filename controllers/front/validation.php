<?php

/**
 * @package Pesepay
 * @author Pesepay <developer@pesepay.com>
 * @link https://pesepay.com
 */

if (!defined('_PS_VERSION_')) {
    exit();
}

/**
 * Validate order and direct customer to pesepay
 *
 * @version 1.0.0
 * @since 1.0.0
 * @author Pesepay <developer@pesepay.com>
 * @copyright Pesepay <developer@pesepay.com>
 */
class PesepayValidationModuleFrontController extends ModuleFrontController
{

    //public $auth = false;
    //public $guestAllowed = true;

    public function postProcess()
    {

        /**
         * @var PaymentModule $module
         */
        $module = $this->module;

        $customer = new Customer($this->context->cart->id_customer);
        if (!Validate::isLoadedObject($customer)) {
            return;
        }

        $total = $this->context->cart->getOrderTotal(true, Cart::BOTH);

        // create order if not yet created
        if ($this->context->cart->OrderExists() == false) {
            $order_status = Configuration::get('PS_OS_PREPARATION');
            $order_currency = $this->context->currency->id;

            $module->validateOrder((int)$this->context->cart->id, $order_status, $total, $module->displayName, null, null, (int)$order_currency, false, $customer->secure_key);
        }

        $ref = Configuration::get('PS_SHOP_NAME') . ' : Order #' . $this->context->cart->id;

        $request = array(
            'id_cart' => $this->context->cart->id,
            'id_module' => $module->id,
            'id_order' => $module->currentOrder,
            'key' => $customer->secure_key
        );

        $links = array(
            "returnUrl" => $this->context->link->getPageLink('order-confirmation', null, null, $request),
            "resultUrl" => $this->context->link->getModuleLink('pesepay', 'status', array("reference" => $module->currentOrder))
        );

        $response = PesepayPaymentUtils::remote_init_transaction($total, $this->context->currency->iso_code, $ref, $links);

        if ($response) {
            if ($response["success"]) {

                PesepayPaymentUtils::insert_transaction($module->currentOrder, $module->name, $response["data"]);

                Tools::redirect($response["data"]["redirectUrl"]);
            } else {
                # Get error message
                PesepayPaymentUtils::redirect_with_message($this->context, $response["data"]["transactionStatusDescription"]);
            }
        } else {
            # Get generic error message
            PesepayPaymentUtils::redirect_with_message($this->context, $this->context->getTranslator()->trans("Failed to initiate transaction."));
        }
    }
}