<?php

/**
 * @package Pesepay
 * @author Pesepay <developer@pesepay.com>
 * @link https://pesepay.com
 */

if (!defined('_PS_VERSION_')) {
	exit();
}

require_once _PS_MODULE_DIR_ . 'pesepay/includes/loader.php';

/**
 * Main Module class
 * 
 * @version 1.0.0
 * @since 1.0.0
 * @author Pesepay <developer@pesepay.com>
 * @copyright Pesepay <developer@pesepay.com>
 */
class Pesepay extends PaymentModule
{

	/**
	 * Plugin set up function
	 *
	 * @access public
	 * @version 1.0.0
	 * @since 1.0.0
	 */
	public function __construct()
	{
		$this->name = 'pesepay';
		$this->version = '1.0.0';
		$this->author = 'Pesepay';
		$this->module_key = '7325ad6589395ea3d96e1a8665078303';
		$this->author_uri = 'https://pesepay.com';
		$this->tab = 'payments_gateways';

		$this->need_instance = 1;
		$this->ps_versions_compliancy = array(
			'min' => '1.6',
			'max' => _PS_VERSION_
		);

		$this->bootstrap = true;

		$this->controllers = array('payment', 'validation', 'status');
		$this->currencies = true;
		$this->currencies_mode = 'checkbox';

		parent::__construct();

		// settings

		$config = Configuration::getMultiple(array(
			'PESEPAY_INTEGRATION_KEY',
			'PESEPAY_ENCRYPTION_KEY'
		));

		if (!$config['PESEPAY_INTEGRATION_KEY'] && !$config['PESEPAY_ENCRYPTION_KEY']) {
			$this->warning = $this->l('You have to provide an integration key and encryption key.');
		} else {
			// only one of the values is empty
			if (!$config['PESEPAY_INTEGRATION_KEY']) {
				$this->warning = $this->l('You have to provide an integration key.');
			}
			if (!$config['PESEPAY_ENCRYPTION_KEY']) {
				$this->warning = $this->l('You have to provide an encryption key.');
			}
		}

		$this->displayName = $this->l('Pesepay Gateway');
		$this->description = $this->l('Pesepay helps businesses in Africa get paid by anyone, anywhere in the world.');
		$this->confirmUninstall = $this->l('Are you sure you want to remove all your settings?');
	}

	/**
	 * Install plugin and register hooks
	 *
	 * @access public
	 * @version 1.0.0
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function install()
	{
		if (PesepayPaymentUtils::isPrestaShop17()) {
			$hooks = array(
				'paymentOptions',
				'displayPaymentTop'
			);
		} else {
			$hooks = array(
				'payment'
			);
		}

		return parent::install() && $this->installDB() && $this->registerHook(array_merge(array(
			'paymentReturn'
		), $hooks));
	}

	/**
	 * Create a database table to track customer orders on pesepay
	 *
	 * @access public
	 * @version 1.0.0
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function installDB()
	{
		return Db::getInstance()->execute('
				CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . $this->name . '` (
					`id` INT(11) NOT NULL AUTO_INCREMENT,
            		`order_id` INT NOT NULL,
            		`payload` TEXT NOT NULL,
            		`time_created` DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
            		PRIMARY KEY (`id`)
				) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8 ;');
	}

	/**
	 * On unistall delete database table
	 *
	 * @access public
	 * @version 1.0.0
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function uninstallDB()
	{
		return Db::getInstance()->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . $this->name . '`');
	}

	/**
	 * Unregister hooks registered on install
	 *
	 * @access public
	 * @version 1.0.0
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function uninstall()
	{
		return parent::uninstall() && $this->uninstallDB() && Configuration::deleteByName('PESEPAY_INTEGRATION_KEY') && Configuration::deleteByName('PESEPAY_ENCRYPTION_KEY');
	}

	/**
	 * Save admin options
	 *
	 * @access public
	 * @version 1.0.0
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function getContent()
	{
		$output = null;

		if (Tools::isSubmit('submit' . $this->name)) {
			$integration_key = (string)Tools::getValue('PESEPAY_INTEGRATION_KEY');
			$encryption_key = (string)Tools::getValue('PESEPAY_ENCRYPTION_KEY');

			$save = true;

			if (!$integration_key || empty($integration_key) || !Validate::isGenericName($integration_key)) {
				$output .= $this->displayError($this->l('Invalid Pesepay Integration key'));
				$save &= false;
			}

			if (!$encryption_key || empty($encryption_key) || !Validate::isGenericName($encryption_key)) {
				$output .= $this->displayError($this->l('Invalid Pesepay Encryption key'));
				$save &= false;
			}

			if ($save && !in_array(Tools::strlen($encryption_key), PesepayPaymentUtils::$ENCRYPTION_KEY_LENGTH)) {
				$output .= $this->displayError($this->l("Encryption Key must be 32 characters in length"));
				$save &= false;
			}

			/**
			 * Allow changing title only on prestashop 1.7+
			 */
			if (PesepayPaymentUtils::isPrestaShop17()) {
				$title = (string)Tools::getValue('PESEPAY_TITLE');
				Configuration::updateValue('PESEPAY_TITLE', $title);
			}

			if ($save) {
				Configuration::updateValue('PESEPAY_INTEGRATION_KEY', $integration_key);
				Configuration::updateValue('PESEPAY_ENCRYPTION_KEY', $encryption_key);

				$output .= $this->displayConfirmation($this->l('Settings updated'));
			}
		}

		return $output . $this->displayForm();
	}

	/**
	 * Get content to display on the plugin set up page
	 *
	 * @access public
	 * @version 1.0.0
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function displayForm()
	{
		// Get default language
		$defaultLang = (int)Configuration::get('PS_LANG_DEFAULT');

		// Init Fields form array
		$fieldsForm = array();
		$fieldsForm[0]['form'] = [
			'legend' => [
				'title' => $this->l('Pesepay Integration Settings')
			],
			'input' => [
				[
					'type' => 'text',
					'label' => $this->l('Integration Key'),
					'name' => 'PESEPAY_INTEGRATION_KEY',
					'required' => true,
					'desc' => $this->l('Pesepay Integration Key.')
				],
				[
					'type' => 'text',
					'label' => $this->l('Encryption Key'),
					'name' => 'PESEPAY_ENCRYPTION_KEY',
					'required' => true,
					'desc' => $this->l('Pesepay Encryption Key.')
				]
			],
			'submit' => [
				'title' => $this->l('Save'),
				'class' => 'btn btn-default pull-right'
			]
		];

		/**
		 * Allow changing title only on prestashop 1.7+
		 */
		if (PesepayPaymentUtils::isPrestaShop17()) {
			array_push($fieldsForm[0]['form']["input"], [
				'type' => 'text',
				'label' => $this->l('Title'),
				'name' => 'PESEPAY_TITLE',
				'required' => false,
				'placeholder' => "Pay via",
				'desc' => $this->l('Call to action to show on front page on checkout.')
			]);
		}

		$helper = new HelperForm();

		// Module, token and currentIndex
		$helper->module = $this;
		$helper->name_controller = $this->name;
		$helper->token = Tools::getAdminTokenLite('AdminModules');
		$helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;

		// Language
		$helper->default_form_language = $defaultLang;
		$helper->allow_employee_form_lang = $defaultLang;

		// Title and toolbar
		$helper->title = $this->displayName;
		$helper->show_toolbar = true; // false -> remove toolbar
		$helper->toolbar_scroll = true; // yes - > Toolbar is always visible on the top of the screen.
		$helper->submit_action = 'submit' . $this->name;
		$helper->toolbar_btn = [
			'save' => [
				'desc' => $this->l('Save'),
				'href' => AdminController::$currentIndex . '&configure=' . $this->name . '&save' . $this->name . '&token=' . Tools::getAdminTokenLite('AdminModules')
			],
			'back' => [
				'href' => AdminController::$currentIndex . '&token=' . Tools::getAdminTokenLite('AdminModules'),
				'desc' => $this->l('Back to list')
			]
		];

		// Load current value
		$encryption_keys = array(
			'PESEPAY_INTEGRATION_KEY',
			'PESEPAY_ENCRYPTION_KEY'
		);

		/**
		 * Allow changing title only on prestashop 1.7+
		 */
		if (PesepayPaymentUtils::isPrestaShop17()) {
			$encryption_keys[] = 'PESEPAY_TITLE';
		}

		$config = Configuration::getMultiple($encryption_keys);

		foreach ($encryption_keys as $encryption_key) {
			$helper->fields_value[$encryption_key] = isset($config[$encryption_key]) ? $config[$encryption_key] : "";
		}

		return $helper->generateForm($fieldsForm);
	}

	/**
	 * Hook to show error messages on front end
	 *
	 * @access public
	 * @version 1.0.0
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function hookDisplayPaymentTop()
	{
		$cookie = $this->context->cookie;

		if ($cookie->PESEPAY_ERROR) {
			$this->smarty->assign('msg', $cookie->PESEPAY_ERROR);
			$cookie->__unset('PESEPAY_ERROR');

			return $this->display(__FILE__, 'paymentInfo.tpl');
		}
	}

	/**
	 * Load checkout payment page
	 * Presta shop 1.6 and below only
	 *
	 * @access public
	 * @version 1.0.0
	 * @since 1.0.0
	 *
	 * @param array $params
	 *
	 * @return void
	 */
	public function hookPayment($params)
	{
		if (!$this->isActive()) {
			return;
		}

		if (!$this->checkCurrency($params['cart'])) {
			return;
		}

		/**
		 * @var FrontController $controller
		 */
		$controller = $this->context->controller;

		if (PesepayPaymentUtils::isPrestaShop17()) {
			$controller->registerStylesheet($this->name, $this->_path . 'views/css/pesepay.css');
		} else {
			/**
			 * @ignore deprecated function
			 */
			$controller->addCSS($this->_path . 'views/css/pesepay.css');
		}

		return $this->display(__FILE__, 'payment.tpl');
	}

	/**
	 * Check whether current currency if correct for the cart
	 *
	 * @access public
	 * @version 1.0.0
	 * @since 1.0.0
	 *
	 * @param Cart $cart
	 *
	 * @return Boolean
	 */
	public function checkCurrency($cart)
	{
		$currency_order = new Currency($cart->id_currency);
		$currencies_module = $this->getCurrency($cart->id_currency);

		$currencies = PesepayPaymentUtils::remote_supported_currencies();

		if (is_array($currencies_module)) {
			foreach ($currencies_module as $currency_module) {
				if ($currency_order->id == $currency_module['id_currency'] && in_array($currency_order->iso_code, $currencies)) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Whether module is activated or not
	 *
	 * @access public
	 * @version 1.0.0
	 * @since 1.0.0
	 *
	 * @return boolean
	 */
	private function isActive()
	{
		return $this->active && Configuration::hasKey('PESEPAY_INTEGRATION_KEY') && Configuration::hasKey('PESEPAY_ENCRYPTION_KEY');
	}

	/**
	 * Get the checkout page content for presta shop 1.6
	 *
	 * @access public
	 * @version 1.0.0
	 * @since 1.0.0
	 *
	 * @param array $params
	 *
	 * @return String
	 */
	public function hookPaymentReturn($params)
	{
		if (!$this->isActive()) {
			return;
		}

		/**
		 *
		 * @var OrderCore $order
		 */
		$order = "";
		if (PesepayPaymentUtils::isPrestaShop17()) {
			$order = $params['order'];
		} else {
			$order = $params['objOrder'];
		}

		if (!Validate::isLoadedObject($order) || $order->module != $this->name) {
			return;
		}

		// verify order status
		PesepayPaymentUtils::verify_order_status($order, $this);

		$this->smarty->assign(array(
			'status' => $order->valid,
			'shop_name' => $this->context->shop->name
		));

		return $this->display(__FILE__, 'payment_return.tpl');
	}

	/**
	 * Get the checkout page content for presta shop 1.7
	 *
	 * @access public
	 * @version 1.0.0
	 * @since 1.0.0
	 *
	 * @param array $params
	 *
	 * @return array
	 */
	public function hookPaymentOptions($params)
	{

		if (!$this->isActive()) {
			return;
		}

		if (!$this->checkCurrency($params['cart'])) {
			return;
		}

		if (Configuration::hasKey("PESEPAY_TITLE")) {
			$title = Configuration::get("PESEPAY_TITLE");
		}

		if (empty($title)) {
			$title = $this->l('Pay via');
		}

		$newOption = PesepayPaymentCompat::getPaymentOption($this->name, $title, $this->_path . 'views\img\pesepay-badge.png', $this->context->link->getModuleLink($this->name, 'validation', array()));

		$payment_options = [
			$newOption
		];

		return $payment_options;
	}

	/**
	 * The url where order status updates will be sent by pesepay
	 *
	 * @access public
	 * @param int $order
	 * @version 1.0.0
	 * @since 1.0.0
	 *
	 * @return String
	 */
	public function getOrderStatusUrl($order)
	{
		return	$this->context->link->getModuleLink($this->name, 'status', array("order_id" => $order), true);
	}
}