<?php

/**
 * 奖池设置
 */
namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use think\Db;

class JackpotController extends AdminbaseController {

    function set(){

        $config=Db::name("option")->where("option_name='jackpot'")->value("option_value");

		$this->assign('config',json_decode($config,true) );
    	
    	return $this->fetch();
    }
    
    function setPost(){
        if ($this->request->isPost()) {
            
            $config = $this->request->param('post/a');
            
			$rs = DB::name('option')->where("option_name='jackpot'")->update(['option_value'=>json_encode($config)] );
            if($rs===false){
                $this->error("保存失败！");
            }
            $key='jackpotset';
            setcaches($key,$config);
            
            $this->success("保存成功！");
            
		}
    }
    
    function index(){
        
        $lists = Db::name("jackpot_level")
			->order("levelid asc")
			->paginate(20);
        
        
        $page = $lists->render();

    	$this->assign('lists', $lists);

    	$this->assign("page", $page);
    	
    	return $this->fetch();
        
    }
    
	function del(){
        
        $id = $this->request->param('id', 0, 'intval');
        
        $rs = DB::name('jackpot_level')->where("id={$id}")->delete();
        if(!$rs){
            $this->error("删除失败！");
        }
        
                    
        $this->resetcache();
        $this->success("删除成功！");
            
	}
	
    function add(){
		return $this->fetch();
	}
    function addPost(){
		if ($this->request->isPost()) {
            
            $data      = $this->request->param();
            
			$levelid=$data['levelid'];

			if($levelid==""){
				$this->error("请填写等级");
			}
            
            $check = DB::name('jackpot_level')->where(["levelid"=>$levelid])->find();
            if($check){
                $this->error('等级不能重复');
            }
                
			$level_up=$data['level_up'];
			if($level_up==""){
				$this->error("请填写等级下限");
			}
            
            $data['addtime']=time();
            
			$id = DB::name('jackpot_level')->insertGetId($data);
            if(!$id){
                $this->error("添加失败！");
            }
            
            
            $this->resetcache();
            $this->success("添加成功！");
            
		}
	}
    
    function edit(){
        
        $id   = $this->request->param('id', 0, 'intval');
        
        $data=Db::name('jackpot_level')
            ->where("id={$id}")
            ->find();
        if(!$data){
            $this->error("信息错误");
        }
        
        $this->assign('data', $data);
        return $this->fetch();
	}
	
	function editPost(){
		if ($this->request->isPost()) {
            
            $data      = $this->request->param();
            
			$id=$data['id'];
			$levelid=$data['levelid'];

			if($levelid==""){
				$this->error("请填写等级");
			}
            
            $check = DB::name('jackpot_level')->where([["levelid",'=',$levelid],['id','<>',$id]])->find();
            if($check){
                $this->error('等级不能重复');
            }
                
			$level_up=$data['level_up'];
			if($level_up==""){
				$this->error("请填写等级下限");
			}
            
			$rs = DB::name('jackpot_level')->update($data);
            if($rs===false){
                $this->error("修改失败！");
            }
            
            $action="修改坐骑：{$data['id']}";
            setAdminLog($action);
            
            $this->resetcache();
            $this->success("修改成功！");
		}
	}
    
    function resetcache(){
        $key='jackpot_level';

        $level= DB::name("jackpot_level")->order("level_up asc")->select();
        if($level){
            setcaches($key,$level);
        }else{
			delcache($key);
		}
        
        return 1;
    }       

}
