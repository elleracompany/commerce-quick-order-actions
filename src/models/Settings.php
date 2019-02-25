<?php

namespace ellera\commerce\orders\models;

use craft\base\Model;
use craft\commerce\Plugin as CraftCommerce;
use craft\helpers\ArrayHelper;

class Settings extends Model
{
	public $override = true;

	public $status_processing;

	public $status_complete;

	public $status_cancelled;

	public $status_refunded;

	private $status_dropdown = [];

	public function rules()
	{
		return [];
	}

	public function attributeLabels()
	{
		return [
			'override' => 'Override',
			'status_processing' => 'Processing Status',
			'status_complete' => 'Complete Status',
			'status_cancelled' => 'Cancelled Status',
			'status_refunded' => 'Refunded Status',
		];
	}

	public function getStatusOptions() : array
	{
		if(empty($this->status_dropdown)) $this->status_dropdown = ArrayHelper::map(
			CraftCommerce::getInstance()
				->getOrderStatuses()
				->getAllOrderStatuses(),
			'id', 'name'
		);
		return $this->status_dropdown;
	}
}