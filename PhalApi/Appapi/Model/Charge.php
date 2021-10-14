<?php

class Model_Charge extends PhalApi_Model_NotORM {
	/* 订单号 */
	public function getOrderId($changeid,$orderinfo,$cztype=0) {
	    if($cztype==0){
            $charge=DI()->notorm->charge_rules->select('*')->where('id=?',$changeid)->fetchOne();
            if(!$charge || $charge['money']!=$orderinfo['money'] || $charge['coin']!=$orderinfo['coin'] ){
                return 1003;
            }

            $orderinfo['coin_give']=$charge['give'];
        }else{
            $charge=DI()->notorm->vip->select('*')->where('id=?',$changeid)->fetchOne();
            if(!$charge || $charge['score']!=$orderinfo['money'] ){
                return 1003;
            }

            $orderinfo['coin_give']=$charge['coin'];
            $orderinfo['coin']=$charge['length'];
        }

		

		$result= DI()->notorm->charge_user->insert($orderinfo);

		return $result;
	}			

}
