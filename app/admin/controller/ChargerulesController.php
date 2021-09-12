<?php

/**
 * 充值规则
 */
namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use think\Db;

class ChargerulesController extends AdminbaseController {

		
    function index(){
        
        $lists = Db::name("charge_rules")
			->order("list_order asc")
			->paginate(20);
        
        $page = $lists->render();

    	$this->assign('lists', $lists);

    	$this->assign("page", $page);
    	
    	return $this->fetch();
        
    }		
		
	function del(){
        $id = $this->request->param('id', 0, 'intval');
        
        $rs = DB::name('charge_rules')->where("id={$id}")->delete();
        if(!$rs){
            $this->error("删除失败！");
        }
        
        $action="删除充值规则：{$id}";
        setAdminLog($action);
                    
        $this->resetcache();
        $this->success("删除成功！",url("Chargerules/index"));			
	}
    
    //排序
    public function listOrder() { 
		
        $model = DB::name('charge_rules');
        parent::listOrders($model);
        
        $action="更新充值规则排序";
        setAdminLog($action);
        
        $this->resetcache();
        $this->success("排序更新成功！");
        
    }	

	
    function add(){
		return $this->fetch();
    }	
	
    function addPost(){
		if ($this->request->isPost()) {
            
            $data      = $this->request->param();
            
            $data['addtime']=time();
            
			$id = DB::name('charge_rules')->insertGetId($data);
            if(!$id){
                $this->error("添加失败！");
            }
            
            $action="添加充值规则：{$id}";
            setAdminLog($action);
            
            $this->resetcache();
            $this->success("添加成功！");
            
		}
	}
    
    function edit(){
        $id   = $this->request->param('id', 0, 'intval');
        
        $data=Db::name('charge_rules')
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
            
			$rs = DB::name('charge_rules')->update($data);
            if($rs===false){
                $this->error("修改失败！");
            }
			
            $action="修改充值规则：{$data['id']}";
            setAdminLog($action);
            
            $this->resetcache();
            $this->success("修改成功！");
		}
	}
    	

    function resetcache(){
        $key='getChargeRules';
        $rules= DB::name("charge_rules")
            ->field('id,coin,coin_ios,money,money_ios,product_id,give')
            ->order('list_order asc')
            ->select();
        if($rules){
            setcaches($key,$rules);
        }else{
			delcache($key);
		}
        return 1;
    }
}
