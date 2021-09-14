<?php

/**
 * 礼物
 */
namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use think\Db;

class LiveconfController extends AdminbaseController {
    protected function getTypes($k=''){
        $type=[
            '0'=>'普通礼物',
            '1'=>'豪华礼物',
        ];
        if($k==''){
            return $type;
        }
        return isset($type[$k]) ? $type[$k]: '';
    }
    protected function getMark($k=''){
        $mark=[
            '0'=>'普通',
            '1'=>'热门',
            '2'=>'守护',
            '3'=>'幸运',
        ];
        if($k==''){
            return $mark;
        }
        return isset($mark[$k]) ? $mark[$k]: '';
    }
    
    protected function getSwftype($k=''){
        $swftype=[
            '0'=>'GIF',
            '1'=>'SVGA',
        ];
        if($k==''){
            return $swftype;
        }
        return isset($swftype[$k]) ? $swftype[$k]: '';
    }
    
    function index(){

    	$lists = Db::name("live_conf")
//            ->where('type!=2')
			->order("id desc")
			->paginate(20);
        
//        $lists->each(function($v,$k){
//			$v['gifticon']=get_upload_path($v['gifticon']);
//			$v['swf']=get_upload_path($v['swf']);
//            return $v;
//        });
        
        $page = $lists->render();

    	$this->assign('lists', $lists);

    	$this->assign("page", $page);
        
//    	$this->assign("type", $this->getTypes());
//    	$this->assign("mark", $this->getMark());
//    	$this->assign("swftype", $this->getSwftype());
    	
    	return $this->fetch();
    }
    
	function del(){
        
        $id = $this->request->param('id', 0, 'intval');
        
        $rs = DB::name('live_conf')->where("id={$id}")->delete();
        if(!$rs){
            $this->error("删除失败！");
        }
        
        $action="删除云配置：{$id}";
        setAdminLog($action);
                    
        $this->resetcache();
        $this->success("删除成功！");
        
	}


    function add(){
        
//        $this->assign("type", $this->getTypes());
//    	$this->assign("mark", $this->getMark());
//    	$this->assign("swftype", $this->getSwftype());
        
        return $this->fetch();				
    }

	function addPost(){
		if ($this->request->isPost()) {
            
            $data      = $this->request->param();
            
            $liveconf=$data['liveconf'];
            if($liveconf == ''){
                $this->error('请输入参数');
            }


            
			$id = DB::name('live_conf')->insertGetId($data);
            if(!$id){
                $this->error("添加失败！");
            }
            
            $action="添加云直播配置：{$id}";
            setAdminLog($action);
            
            $this->resetcache();
            $this->success("添加成功！");
            
		}			
	}
    
    function edit(){

        $id   = $this->request->param('id', 0, 'intval');
        
        $data=Db::name('live_conf')
            ->where("id={$id}")
            ->find();
        if(!$data){
            $this->error("信息错误");
        }
        
//        $this->assign("type", $this->getTypes());
//    	$this->assign("mark", $this->getMark());
//    	$this->assign("swftype", $this->getSwftype());
        
        $this->assign('data', $data);
        return $this->fetch();            
    }
    
	function editPost(){
		if ($this->request->isPost()) {
            
            $data      = $this->request->param();

            $liveconf=$data['liveconf'];
            if($liveconf == ''){
                $this->error('请输入参数');
            }

            
			$rs = DB::name('live_conf')->update($data);
            if($rs===false){
                $this->error("修改失败！");
            }
            
            $action="修改云直播配置：{$data['id']}";
            setAdminLog($action);
            
            $this->resetcache();
            $this->success("修改成功！");
		}	
	}

	function start(){
//        if ($this->request->isGet()) {

            $data      = $this->request->param();
            $data['status'] = 1;

            //先把原来的改成不启用
            $res = Db::name('live_conf')
                ->where('status=1')
                ->update(['status'=>0]);
            if($res===false){
                $this->error("更改原数据失败！",'index');
            }
            //然后更新指定的内容
            $rs = DB::name('live_conf')->update($data);
            if($rs===false){
                $this->error("启用失败！",'index');
            }
            //读取配置
            $getData = DB::name('live_conf')->where("id={$data['id']}")->value('liveconf');
            $inData = json_decode($getData,true);
            $getConfPri = cmf_get_option('configpri');
            $getConfPri['tx_appid'] = empty($inData['tx_appid'])?'':$inData['tx_appid'];
            $getConfPri['tx_bizid'] = empty($inData['tx_bizid'])?'':$inData['tx_bizid'];
            $getConfPri['tx_push_key'] = empty($inData['tx_push_key'])?'':$inData['tx_push_key'];
            $getConfPri['tx_api_key'] = empty($inData['tx_api_key'])?'':$inData['tx_api_key'];
            $getConfPri['tx_push'] = empty($inData['tx_push'])?'':$inData['tx_push'];
            $getConfPri['tx_pull'] = empty($inData['tx_pull'])?'':$inData['tx_pull'];
            $getConfPri['tx_acc_key'] = empty($inData['tx_acc_key'])?'':$inData['tx_acc_key'];
//            var_dump($getConfPri);
//            die();
            //写入配置
            cmf_set_option('configpri', $getConfPri);

            $action="启用云直播配置：{$data['id']}";
            setAdminLog($action);

            $this->resetcache();
            $this->success("启用成功！",'index');
//        }
    }

    function stop(){
//        if ($this->request->isGet()) {

            $data      = $this->request->param();
            $data['status'] = 0;

            $rs = DB::name('live_conf')->update($data);
            if($rs===false){
                $this->error("关闭失败！",'index');
            }

            $action="关闭云直播配置：{$data['id']}";
            setAdminLog($action);

            $this->resetcache();
            $this->success("关闭成功！",'index');
//        }
    }
        
    function resetcache(){
        $key='getLiveconfList';
        
		$rs=DB::name('live_conf')
//			->field("id,type,mark,giftname,needcoin,gifticon,sticker_id,swftime,isplatgift")
//            ->where('type!=2')
			->order("id desc")
			->select();
        if($rs){
            setcaches($key,$rs);
        }else{
			delcache($key);
		}
        return 1;
    }

    protected function resetcacheConf($key='',$info=[]){
        if($key!='' && $info){
            delcache($key);
            setcaches($key,$info);
        }
    }
}
