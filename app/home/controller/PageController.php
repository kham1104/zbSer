<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2014 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: Dean <zxxjjforever@163.com>
// +----------------------------------------------------------------------
namespace app\home\controller;

use cmf\controller\HomeBaseController;
use think\Db;

use app\portal\model\PortalPostModel;
/**
 * 单页
 */
class PageController extends HomebaseController {
	
    //服务条款
	public function agreement() {
        
        $id=4;
        $portalPostModel = new PortalPostModel();
        $agreement            = $portalPostModel->where('id', $id)->find();
        
        $this->assign("agreement",$agreement);
			
    	return $this->fetch();
    }	


}


