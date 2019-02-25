<?php

namespace ellera\commerce\orders;

use craft\events\RegisterUrlRulesEvent;
use craft\web\UrlManager;
use yii\base\Event;
use ellera\commerce\orders\models\Settings;

/**
 * Plugin represents the Action Button plugin.
 *
 * @author Ellera AS <support@ellera.no>
 * @since  1.0
 */
class Plugin extends \craft\base\Plugin
{
	// Attributes
	// =========================================================================

	public $hasCpSettings = true;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();
		$settings = $this->getSettings();

		// Check if the settings are correct, if not use Commerce Default
		if(
			$settings->override === '1' &&
			is_numeric($settings->status_cancelled) &&
			is_numeric($settings->status_complete) &&
			is_numeric($settings->status_refunded) &&
			is_numeric($settings->status_processing)
		) $this->_registerCpRoutes();
	}

	// Protected Methods
	// =========================================================================

	/**
	 * Returns the plugin settings model
	 *
	 * @return Settings
	 */
	protected function createSettingsModel() : Settings
	{
		return new Settings();
	}

	/**
	 * Default Plugin function for the settings page
	 *
	 * @return null|string
	 * @throws \Twig_Error_Loader
	 * @throws \yii\base\Exception
	 */
	protected function settingsHtml()
	{
		return \Craft::$app->getView()->renderTemplate('quick-order-actions/settings', [
			'settings' => $this->getSettings()
		]);
	}

	// Private Methods
	// =========================================================================

	/**
	 * This function overrides the default routes for orders in Craft Commerce.
	 * The controller the new route points to extends the original controller, and delivers
	 * all the original functionality with added buttons.
	 */
	private function _registerCpRoutes() : void
	{
		Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function (RegisterUrlRulesEvent $event) {
			$event->rules['commerce/orders'] = 'quick-order-actions/orders/order-index';
			$event->rules['commerce/orders/<orderId:\d+>'] = 'quick-order-actions/orders/edit-order';
			$event->rules['commerce/orders/action/<orderId:\d+>'] = 'quick-order-actions/orders/order-action';
		});
	}
}
