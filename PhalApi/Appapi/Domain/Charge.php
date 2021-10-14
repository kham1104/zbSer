<?php

class Domain_Charge {
	public function getOrderId($changeid,$orderinfo,$cztype=0) {
		$rs = array();

		$model = new Model_Charge();
		$rs = $model->getOrderId($changeid,$orderinfo,$cztype);

		return $rs;
	}
	
}
