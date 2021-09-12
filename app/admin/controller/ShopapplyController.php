<?php

/**
 * 店铺申请
 */
namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use think\Db;

class ShopapplyController extends AdminbaseController {
    protected function getStatus($k=''){
        $status=array(
            '0'=>'待处理',
            '1'=>'审核成功',
            '2'=>'审核失败',
        );
        if($k==''){
            return $status;
        }
        return isset($status[$k])?$status[$k]:'';
    }
    
    function index(){
        $data = $this->request->param();
        $map=[];
        
        $start_time=isset($data['start_time']) ? $data['start_time']: '';
        $end_time=isset($data['end_time']) ? $data['end_time']: '';
        
        if($start_time!=""){
           $map[]=['addtime','>=',strtotime($start_time)];
        }

        if($end_time!=""){
           $map[]=['addtime','<=',strtotime($end_time) + 60*60*24];
        }
        
        $status=isset($data['status']) ? $data['status']: '';
        if($status!=''){
            $map[]=['status','=',$status];
        }
        
        $uid=isset($data['uid']) ? $data['uid']: '';
        if($uid!=''){
            $lianguid=getLianguser($uid);
            if($lianguid){
                $map[]=['uid',['=',$uid],['in',$lianguid],'or'];
            }else{
                $map[]=['uid','=',$uid];
            }
        }

    	$lists = Db::name("shop_apply")
                ->where($map)
                ->order("addtime DESC")
                ->paginate(20);
                
        $lists->each(function($v,$k){
			//$v['thumb']=get_upload_path($v['thumb']);
            $v['userinfo']= getUserInfo($v['uid']);
            $v['tel']= m_s($v['uid']);
            $v['cardno']=m_s($v['cardno']);
            $v['phone']=m_s($v['phone']);
            $v['classname']='';

            //获取商家经营类目
            $class_list=Db::name("seller_goods_class")->where("uid={$v['uid']}")->select()->toArray();
            foreach ($class_list as $k1 => $v1) {
                $gc_name=Db::name("shop_goods_class")->where("gc_id={$v1['goods_classid']}")->value('gc_name');
                $v['classname'].=$gc_name.' ';
            }

           
            return $v;           
        });
                
        $lists->appends($data);
        $page = $lists->render();


    	$this->assign('lists', $lists);

    	$this->assign("page", $page);
        
    	$this->assign("status", $this->getStatus());
    	
    	return $this->fetch();			
    }
    
	function del(){
        
        $id = $this->request->param('id', 0, 'intval');
        
        $rs = DB::name('shop_apply')->where("uid={$id}")->delete();
        if(!$rs){
            $this->error("删除失败！");
        }
        
        //删除店铺总评分记录
        Db::name("shop_points")->where("shop_uid={$id}")->delete();

        //删除店铺商品
        Db::name("shop_goods")->where("uid={$id}")->delete();
        

        $this->success("删除成功！",url("shopapply/index"));
            
	}
	
    
    function edit(){
        $id   = $this->request->param('id', 0, 'intval');
        
        $data=Db::name('shop_apply')
            ->where("uid={$id}")
            ->find();
        if(!$data){
            $this->error("信息错误");
        }
        
        //$data['thumb']= get_upload_path($data['thumb']);
        $data['certificate']= get_upload_path($data['certificate']);
        //$data['license']= get_upload_path($data['license']);
        $data['other']= get_upload_path($data['other']);
        $data['userinfo']= getUserInfo($data['uid']);

        //获取一级店铺分类
        $oneGoodsClass=getcaches("oneGoodsClass");

        if(!$oneGoodsClass){
            $oneGoodsClass=Db::name("shop_goods_class")->field("gc_id,gc_name,gc_isshow")->where("gc_parentid=0")->order("gc_sort")->select()->toArray();

            setcaches("oneGoodsClass",$oneGoodsClass);
        }

        //获取用户的经营类目
        $seller_class_arr=Db::name("seller_goods_class")->where("uid={$data['uid']}")->select()->toArray();


        $seller_class_arr=array_column($seller_class_arr, 'goods_classid');

        $seller_class=implode(",",$seller_class_arr);

     
        $this->assign('data', $data);
        $this->assign('oneGoodsClass', $oneGoodsClass);
        $this->assign('seller_class', $seller_class);
        
        $this->assign("status", $this->getStatus());
        
        return $this->fetch();
        
    }
    
	function editPost(){
		if ($this->request->isPost()) {
            
            $data = $this->request->param();

            $classids=isset($data['classids'])?$data['classids']:[];
            $count=count($classids);
            if($count<1){
                $this->error("请选择经营类目");
            }
            if($count>3){
                $this->error("经营类目不能超过三个");
            }

            $uid=$data['uid'];
            $order_percent=$data['order_percent'];

            if($order_percent<0||!is_numeric($order_percent)||$order_percent>100){
                $this->error("请填写0-100之间的整数");
            }

            if(floor($order_percent)!=$order_percent){
                $this->error("请填写0-100之间的整数");
            }

            unset($data['classids']);

            $shop_status=$data['status'];

            $reason=$data['reason'];

            if($shop_status==2){ //审核失败
                if(trim($reason)==""){
                    $this->error("请填写审核失败原因");
                }
            }

            $data['uptime']=time();
			$rs = DB::name('shop_apply')->update($data);
            if($rs===false){
                $this->error("修改失败！");
            }

            //更新用户经营类目
            Db::name("seller_goods_class")->where("uid={$uid}")->delete();
            foreach ($classids as $k => $v) {

                //获取一级分类的状态
                $status=Db::name("shop_goods_class")->where("gc_id={$v}")->value('gc_isshow');

                $data1=array(
                    'uid'=>$uid,
                    'goods_classid'=>$v,
                    'status'=>$status
                );
                Db::name("seller_goods_class")->where("uid={$uid}")->insert($data1);
            }

            


            if($shop_status!=1){

                //将店铺内上架的商品下架
                Db::name("shop_goods")->where("uid={$uid} and status=1")->update(array('status'=>-2));
            }

            

            if($shop_status==1){ //审核通过
                $title='你的店铺审核已通过。';
            }else if($shop_status==2){ //审核失败
                $title='你的店铺审核失败。';
                if($reason){
                    $title.='失败原因：'.$reason;
                }
            }

            //写入记录
            $id=addSysytemInfo($uid,$title,1);
            jPush($uid,$title);
            
            $this->success("修改成功！");
		}
	}    
}
