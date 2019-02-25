<?php


namespace ellera\commerce\orders\elements;

use Craft;
use craft\helpers\Html;

class Order extends \craft\commerce\elements\Order
{
	/**
	 * @inheritdoc
	 */
	public function getTableAttributeHtml(string $attribute): string
	{
		if($attribute !== 'orderActions') return parent::getTableAttributeHtml($attribute);

		$settings = \ellera\commerce\orders\Plugin::getInstance()->getSettings();
		$buttons = [];

		// Processing
		if(!in_array($this->orderStatusId, [$settings->status_complete, $settings->status_cancelled, $settings->status_refunded])) {
			if($this->orderStatusId === $settings->status_processing) {
				if(!$this->isPaid) {
					$buttons[] = [
						'title' => 'Capture and mark as complete',
						'icon' => 'fas fa-check',
						'value' => 'capture-complete',
					];
					$buttons[] = [
						'title' => 'Cancel',
						'icon' => 'fas fa-times',
						'value' => 'cancel',
						'class' => 'submit'
					];
				}
				else {
					$buttons[] = [
						'title' => 'Mark as complete',
						'icon' => 'fas fa-check',
						'value' => 'complete',
					];
					if($this->getLastTransaction()->canRefund()) {
						$buttons[] = [
							'title' => 'Refund',
							'icon' => 'fas fa-reply',
							'value' => 'refund',
							'class' => 'submit'
						];
					}
				}
			}
			else {
				$buttons[] = [
					'title' => 'Start processing',
					'icon' => 'fas fa-ellipsis-h',
					'value' => 'process',
				];
				if(!$this->isPaid) {
					$buttons[] = [
						'title' => 'Cancel',
						'icon' => 'fas fa-times',
						'value' => 'cancel',
						'class' => 'submit'
					];
				}
				elseif($this->getLastTransaction()->canRefund()) {
					$buttons[] = [
						'title' => 'Refund',
						'icon' => 'fas fa-reply',
						'value' => 'refund',
						'class' => 'submit'
					];
				}
			}
		}
		// Completed
		else {
			$buttons[] = [
				'title' => 'Download PDF',
				'icon' => 'far fa-file-pdf',
				'value' => 'download',
			];
		}

		$html = Html::beginForm('orders/action/'.$this->id);

		foreach ($buttons as $button) $html .= $this->generateButtonHtml($button);

		return $html.Html::endForm();
	}

	private function generateButtonHtml(array $button) : string
	{
		return Html::submitButton(
			Html::tag('i','',
				['class' => $button['icon']]
			),
			[
				'value' => $button['value'],
				'name' => 'order-action',
				'class' => isset($button['class']) ? 'btn small '.$button['class'] : 'btn small',
				'style' => 'margin-right: 4px; width: 25px;',
				'title' => $button['title']
			]);
	}

	/**
	 * @inheritdoc
	 */
	protected static function defineTableAttributes(): array
	{
		$attributes = parent::defineTableAttributes();
		$attributes['orderActions'] = ['label' => Craft::t('commerce', 'Actions')];
		return $attributes;
	}
}