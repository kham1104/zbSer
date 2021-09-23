<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2019 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | 直播开发联系QQ：1729943308
// +----------------------------------------------------------------------
namespace app\portal\controller;

use cmf\controller\HomeBaseController;

class IndexController extends HomeBaseController
{

    // 首页
    public function index()
    {
        return $this->fetch();
    }
    
    public function scanqr() {
        $code = empty($_REQUEST['code'])?'':$_REQUEST['code'];
        $this->assign('code',$code);
    	return $this->fetch();
    }

}

