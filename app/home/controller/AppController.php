<?php

namespace app\home\controller;

use cmf\controller\HomeBaseController;
use think\Db;

class AppController extends HomebaseController{

    public function programe(){

    	$this->assign("current","download");

        return $this->fetch();
    }



}
