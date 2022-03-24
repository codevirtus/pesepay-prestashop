<?php
if (!defined('_PS_VERSION_')) {
    exit();
}

/**
 * Update order status
 *
 * @version 1.0.0
 * @since 1.0.0
 * @author Pesepay <developer@pesepay.com>
 * @copyright Pesepay <developer@pesepay.com>
 */
class PesepayStatusModuleFrontController extends ModuleFrontController
{

    /**
     * Update order status as instructed by pesepay
     *
     * @access public
     * @version 1.0.0
     * @since 1.0.0
     * @see FrontController::initContent()
     *
     * @return void
     */
    public function postProcess()
    {

        $order = new Order((int)Tools::getValue('reference'));
        // check if order found
        if (Validate::isLoadedObject($order)) {

            /**
             * @var Pesepay $module
             */
            $module = $this->module;

            PesepayPaymentUtils::verify_order_status($order, $module);
        }
    }
}