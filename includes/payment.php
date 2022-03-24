<?php

/**
 * This file houses utility functions to interact with pesepay
 * 
 * @package Codevirtus\Pesepay
 * @author Pesepay <developer@pesepay.com>
 * @link http://pesepay.com
 */

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;

/**
 * Utility class
 * 
 * Class to handle all pesepay miscellaneous api operations
 * 
 * @author Pesepay <developer@pesepay.com>
 * @since 1.0.0
 * @version 1.0.0
 */
class PesepayPaymentUtils
{

    /**
     * Save transaction status
     *
     * @since 1.0.0
     * @version 1.0.0
     * @param int $order_id
     * @param array|string $data
     * @return void
     */
    public static function insert_transaction($order_id, $table, $data)
    {

        $data = self::db_serialize_content($data);

        Db::getInstance()->insert($table, array(
            "order_id" => $order_id,
            "payload" => $data
        ));
    }

    /**
     * Update the details of a transaction
     *
     * @since 1.0.0
     * @version 1.0.0
     * @param int $order_id
     * @param array $data
     * @return void
     */
    public static function update_transaction($order_id, $table, $data = array())
    {

        $data = self::db_serialize_content($data);

        Db::getInstance()->update($table, array(
            "payload" => $data
        ), "order_id='" . $order_id . "'");
    }

    /**
     * Retrieve transaction by order id
     *
     * @since 1.0.0
     * @version 1.0.0
     * @param int $order_id
     * @return array
     */
    public static function retrieve_transaction($order_id, $table)
    {

        $data =  Db::getInstance()->getValue("SELECT `payload` FROM `" . _DB_PREFIX_ . $table . "` WHERE `order_id` = '" . $order_id . "';");

        return self::db_unserialize_content($data);
    }

    /**
     * Serialize given data 
     *
     * @since 1.0.0
     * @version 1.0.0
     * @param array|string $data
     * @return string
     */
    public static function db_serialize_content($data)
    {
        return serialize($data);
    }

    /**
     * Unserialize given data
     * 
     * @since 1.0.0
     * @version 1.0.0
     * @param string $data
     * @return array|string
     */
    public static function db_unserialize_content($data)
    {
        return Tools::unserialize($data);
    }

    /**
     * Api functions
     */

    /**
     * Length of encryption key
     *
     * @since 1.0.0
     * @version 1.0.0
     * @var array|integer
     */
    public static $ENCRYPTION_KEY_LENGTH = array(16, 32);

    /**
     * The algorithm to use for the encryption
     *
     * @since 1.0.0
     * @version 1.0.0
     * @var string
     */
    private static $ENCRYPTION_ALGORITHM = "aes-256-cbc";

    /**
     * The base url to build upon requests
     *
     * @since 1.0.0
     * @version 1.0.0
     * @var string
     */
    private static $URL_BASE = "http://api.pesepay.com/api/payments-engine/";

    /**
     * Initiate a transaction on pesepay
     *
     * @param float $amount
     * @param string $currency
     * @param string $reason
     * @param array $links
     * @return array|bool
     */
    public static function remote_init_transaction($amount, $currency, $reason, $links)
    {

        $url = "v1/payments/initiate";

        $data = array(
            "amountDetails" => array(
                "amount" => $amount,
                "currencyCode" => $currency
            ),
            "reasonForPayment" => $reason,
            "resultUrl" => $links["resultUrl"],
            "returnUrl" => $links["returnUrl"]
        );

        return self::remote_request($url, $data);
    }

    /**
     * Check the status of a transaction
     *
     * @since 1.0.0
     * @version 1.0.0
     * @param string $reference
     * @return array|bool
     */
    public static function remote_check_transaction($reference)
    {

        $url = "v1/payments/check-payment";

        $data = array(
            "referenceNumber" => $reference
        );

        $response = self::remote_request($url, $data, "GET");

        $response["success"] = isset($response["data"]["transactionStatus"]) && $response["data"]["transactionStatus"] == "SUCCESS";

        return $response;
    }

    /**
     * Perform remote request to pesepay
     *
     * @since 1.0.0
     * @version 1.0.0
     * @param string $url
     * @param string $payload
     * @return array|bool
     */
    private static function remote_request($url, $payload = "", $method = "POST")
    {

        $url = rtrim($url, "/\\");

        $headers = array(
            'Authorization' => Configuration::get("PESEPAY_INTEGRATION_KEY")
        );

        $config = array(
            "base_url" => self::$URL_BASE
        );

        $client = new GuzzleHttp\Client($config);

        /**
         * Post takes different params from get
         */
        try {

            switch (Tools::strtoupper($method)) {
                case "POST":
                    if (is_array($payload)) {
                        $payload = json_encode($payload);
                    }

                    $data = self::content_encrypt(Configuration::get("PESEPAY_ENCRYPTION_KEY"), $payload);
                    $payload = array("payload" => $data);

                    $response =   $client->post($url, array(
                        "body" => json_encode($payload),
                        "headers" => array_merge($headers, array(
                            'Content-Type' => 'application/json'
                        ))
                    ));

                    break;
                case "GET":

                    $url =   self::url_append_param($url, $payload);

                    $response =   $client->get($url, array(
                        "headers" => $headers
                    ));
                    break;
            }
        } catch (ClientException | ConnectException $e) {
            $response = false;
        }

        if ($response && $response->getStatusCode() == 200) {

            $payload = $response->getBody()->getContents();

            if ($payload) {

                $payload = json_decode($payload, true);

                if (isset($payload["payload"])) {

                    $data = self::content_decrypt(Configuration::get("PESEPAY_ENCRYPTION_KEY"), $payload["payload"]);
                    $success = true;
                } else {
                    $data =  $payload["message"];
                    $success = false;
                }
                return array(
                    "success" => $success,
                    "data" => json_decode($data, true)
                );
            }
        }
        return false;
    }

    /**
     * Retrieve the list of currencies that can be processed by the system
     *
     * @since 1.0.0
     * @version 1.0.0
     * @return array
     */
    public static function remote_supported_currencies()
    {

        $url = "v1/currencies/active";

        $config = array(
            "base_url" => self::$URL_BASE
        );

        $client = new GuzzleHttp\Client($config);

        try {

            $response =   $client->get($url, array());
        } catch (ClientException | ConnectException $e) {
            $response = false;
        }

        $currencies = array("USD");
        if ($response && $response->getStatusCode() == 200) {

            $payload = $response->getBody()->getContents();

            if ($payload) {

                $payload = json_decode($payload, true);

                $currencies = array_column($payload, "code");
            }
        }

        $currencies = array_map("Tools::strtoupper", $currencies);

        return $currencies;
    }

    /**
     * Decrypt content with given key
     *
     * @since 1.0.0
     * @version 1.0.0
     * @param string $key
     * @param string $content
     * @return string
     */
    private static function content_decrypt($key, $content = "")
    {

        $iv = self::encryption_key_get_iv($key);

        return openssl_decrypt($content, self::$ENCRYPTION_ALGORITHM, $key, 0, $iv);
    }

    /**
     * Encrypt content with given key
     *
     * @since 1.0.0
     * @version 1.0.0
     * @param string $key
     * @param string $content
     * @return string
     */
    private static function content_encrypt($key, $content = "")
    {

        $iv = self::encryption_key_get_iv($key);

        return openssl_encrypt($content, self::$ENCRYPTION_ALGORITHM, $key, 0, $iv);
    }

    /**
     * Get initialisation vector for key
     *
     * @since 1.0.0
     * @version 1.0.0
     * @param string $key
     * @return string
     */
    private static function encryption_key_get_iv($key)
    {
        return Tools::substr($key, 0, self::$ENCRYPTION_KEY_LENGTH[0]);
    }

    /**
     * Helper Functions
     */

    /**
     * Append params to an existing url
     * 
     * Handles cases where the url already has parameters
     *
     * @since 1.0.0
     * @version 1.0.0
     * @param string $url
     * @param array|string $params
     * @return string
     */
    public static function url_append_param($url, $params)
    {

        if (is_array($params)) {
            $params = http_build_query($params);
        }

        if ($params) {
            $url = Tools::url($url, $params);
        }

        return $url;
    }

    /**
     * Get a param from a url if it exists
     *
     * @since 1.0.0
     * @version 1.0.0
     * @param string $url
     * @param string $param
     * @return string
     */
    public static function url_get_param($url, $param = "")
    {

        $value = "";

        $query = parse_url($url, PHP_URL_QUERY);
        parse_str($query, $args);

        if ($param && isset($args[$param])) {
            $value = $args["order_id"];
        }

        return $value;
    }

    /**
     * Redirect users to the right page on error processing order
     *
     * @access public
     * @version 1.0.0
     * @since 1.0.0
     *
     * @param Context $context
     * @param String $message
     *
     * @return void
     */
    public static function redirect_with_message(Context $context, String $message)
    {
        $context->cookie->__set('PESEPAY_ERROR', $message);

        if (self::isPrestaShop17()) {
            Tools::redirect($context->link->getPageLink('order'));
        } else {
            Tools::redirect($context->link->getModuleLink('pesepay', 'payment', array(), true));
        }
    }

    /**
     * Check if running on prestashop 1.7 or above
     *
     * @access public
     * @version 1.0.0
     * @since 1.0.0
     *
     * @return void
     */
    public static function isPrestaShop17()
    {
        return Tools::version_compare(Tools::checkPhpVersion(), '1.7', '>=');
    }

    /**
     * Update order status
     *
     * @access public
     * @version 1.0.0
     * @since 1.0.0
     *
     * @param OrderCore $order
     * @param Pesepay $module
     *
     * @return void
     */
    public static function verify_order_status(Order $order, Pesepay $module)
    {

        $transaction = self::retrieve_transaction($order->id, $module->name);

        $response = self::remote_check_transaction($transaction["referenceNumber"]);

        if ($response) {

            # Save the reference number and/or poll url (used to check the status of a transaction)
            self::update_transaction($order->id, $module->name, $response["data"]);

            #order status
            switch (Tools::strtoupper($response["data"]["transactionStatus"])) {
                case "CANCELLED":
                    $order->setCurrentState((int)Configuration::get('PS_OS_CANCELED'));
                    break;
                case "SUCCESS":
                    $order->setCurrentState((int)Configuration::get('PS_OS_PAYMENT'));
                    break;
                case "FAILED":
                default:
                    $order->setCurrentState((int)Configuration::get('PS_OS_ERROR'));
            }
        } else {
            $order->setCurrentState((int)Configuration::get('PS_OS_ERROR'));
        }
    }
}