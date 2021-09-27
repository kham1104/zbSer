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

require __DIR__ . '/../../../plugins/phpapkpacker/ApkPacker.php';
use cmf\controller\HomeBaseController;
use plugins\ApkPacker\ApkPacker;
use think\facade\Env;

class IndexController extends HomeBaseController
{

    // 首页
    public function index()
    {
        return $this->fetch();
    }
    
    public function scanqr() {

//        var_dump($this->int2bytes(8));die();

        $apk = new ApkPacker();
        $old = Env::get('root_path').'public/111.apk';
//        var_dump($old);die;
        $new = Env::get('root_path').'public/222.apk';
        $apk->packerSingleApk($old,'abcdef',$new);
        $code = empty($_REQUEST['code'])?'':$_REQUEST['code'];
        $this->assign('code',$code);
    	return $this->fetch();
    }

    private function int2bytes($num){
        $byt = [];
        $byt[0] = ($num & 0xff);
        $byt[1] = ($num >> 8 & 0xff);
        $byt[2] = ($num >> 16 & 0xff);
        $byt[3] = ($num >> 24 & 0xff);
        return $byt;
    }


}

