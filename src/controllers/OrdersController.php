<?php

namespace ellera\commerce\orders\controllers;

use Craft;
use craft\commerce\models\OrderStatus;
use craft\commerce\Plugin;
use yii\web\Response;
use craft\commerce\errors\RefundException;
use craft\commerce\records\Transaction as TransactionRecord;
use craft\commerce\models\Transaction;
use craft\commerce\elements\Order;

/**
 * Class Orders Controller
 *
 * @author Ellera AS <support@ellera.no>
 */
class OrdersController extends \craft\commerce\controllers\OrdersController
{
	/**
	 * Index of Orders
	 * Overwriting commerce/controllers/OrderController::actionOrderIndex()
	 *
	 * @return Response
	 * @throws \Throwable
	 */
	public function actionOrderIndex(): Response
	{
		// Remove all incomplete carts older than a certain date in config.
		Plugin::getInstance()->getCarts()->purgeIncompleteCarts();

		Craft::$app->view->registerCssFile('https://use.fontawesome.com/releases/v5.3.1/css/all.css');

		return $this->renderTemplate('quick-order-actions/orders/_index');
	}

	/**
	 * @param string $orderId
	 *
	 * @return Response
	 * @throws \Throwable
	 * @throws \craft\errors\ElementNotFoundException
	 * @throws \craft\errors\MissingComponentException
	 * @throws \yii\base\Exception
	 */
	public function actionOrderAction(string $orderId) : Response
	{
		if(!Craft::$app->request->isPost || !Craft::$app->request->validateCsrfToken()) Craft::$app->getSession()->setError(Craft::t('commerce', 'CSRF Validation failed'));
		$order = Plugin::getInstance()->getOrders()->getOrderById($orderId);
		$transaction = $order->getLastTransaction();
		$settings = \ellera\commerce\orders\Plugin::getInstance()->getSettings();
		if(!$order) Craft::$app->getSession()->setError(Craft::t('commerce', 'Couldn’t find requested Order'));
		$updateStatus = false;
		switch(Craft::$app->request->post('order-action')) {
			case 'process':
				$orderStatus = Plugin::getInstance()->getOrderStatuses()->getOrderStatusById($settings->status_processing);
				if($orderStatus->id !== $order->orderStatusId) $updateStatus = true;
				break;
			case 'cancel':
				$orderStatus = Plugin::getInstance()->getOrderStatuses()->getOrderStatusById($settings->status_cancelled);
				if($orderStatus->id !== $order->orderStatusId) $updateStatus = true;
				break;
			case 'refund':
				$orderStatus = Plugin::getInstance()->getOrderStatuses()->getOrderStatusById($settings->status_refunded);
				$updateStatus = $this->_refund($order, $transaction, $orderStatus);
				break;
			case 'complete':
				$orderStatus = Plugin::getInstance()->getOrderStatuses()->getOrderStatusById($settings->status_complete);
				if($orderStatus->id !== $order->orderStatusId) $updateStatus = true;
				break;
			case 'capture-complete':
				$orderStatus = Plugin::getInstance()->getOrderStatuses()->getOrderStatusById($settings->status_complete);
				$updateStatus = $this->_capture($order, $transaction, $orderStatus);
				break;
			case 'download':
				return $this->redirect($order->getPdfUrl());
				break;
			default:
				Craft::$app->getSession()->setError(Craft::t('commerce', 'Requested order action not found'));
		}
		if($updateStatus) {
			$order->orderStatusId = $orderStatus->id;
			if (!Craft::$app->getElements()->saveElement($order)) {
				Craft::$app->getSession()->setError(Craft::t('commerce', 'Couldn’t update order status'));
			}
		}
		return $this->redirect('commerce/orders');
	}

	/**
	 * @param Order       $order
	 * @param Transaction $transaction
	 * @param OrderStatus $orderStatus
	 *
	 * @return bool
	 * @throws \craft\errors\MissingComponentException
	 */
	private function _refund(Order $order, Transaction $transaction, OrderStatus $orderStatus)
	{
		if($orderStatus->id !== $order->orderStatusId && $transaction->canRefund()) {
			try {
				// refund transaction and display result
				$child = Plugin::getInstance()->getPayments()->refundTransaction($transaction, $transaction->getRefundableAmount(), 'Refunded with Easy Order Management');
				$message = $child->message ? ' (' . $child->message . ')' : '';

				if ($child->status == TransactionRecord::STATUS_SUCCESS) {
					Craft::$app->getSession()->setNotice(Craft::t('commerce', 'Transaction refunded successfully: {message}', [
						'message' => $message
					]));
					return true;
				} else {
					Craft::$app->getSession()->setError(Craft::t('commerce', 'Couldn’t refund transaction: {message}', [
						'message' => $message
					]));
				}
			} catch (RefundException $exception) {
				Craft::$app->getSession()->setError($exception->getMessage());
			}
		}
		return false;
	}

	/**
	 * @param Order       $order
	 * @param Transaction $transaction
	 * @param OrderStatus $orderStatus
	 *
	 * @return bool
	 * @throws \craft\commerce\errors\TransactionException
	 * @throws \craft\errors\MissingComponentException
	 */
	private function _capture(Order $order, Transaction $transaction, OrderStatus $orderStatus)
	{
		if($orderStatus->id !== $order->orderStatusId && $transaction->canCapture()) {
			// capture transaction and display result
			$child = Plugin::getInstance()->getPayments()->captureTransaction($transaction);

			$message = $child->message ? ' (' . $child->message . ')' : '';

			if ($child->status == TransactionRecord::STATUS_SUCCESS) {
				$child->order->updateOrderPaidInformation();
				Craft::$app->getSession()->setNotice(Craft::t('commerce', 'Transaction captured successfully: {message}', [
					'message' => $message
				]));
				return true;
			} else {
				Craft::$app->getSession()->setError(Craft::t('commerce', 'Couldn’t capture transaction: {message}', [
					'message' => $message
				]));
			}
		}
		return false;
	}
}
