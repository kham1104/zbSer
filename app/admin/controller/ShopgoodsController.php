<?php

/**
 * 商品
 */
namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use think\Db;

class ShopgoodsController extends AdminbaseController {
    
    
    protected function getStatus($k=''){
        $status=[
            '-2'=>'管理员下架',
            '-1'=>'商家下架',
            '0'=>'审核中',
            '1'=>'通过',
            '2'=>'拒绝',
        ];
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
        
        $uid=isset($data['uid']) ? $data['uid']: '';
        if($uid!=''){
            $lianguid=getLianguser($uid);
            if($lianguid){
                $map[]=['uid',['=',$uid],['in',$lianguid],'or'];
            }else{
                $map[]=['uid','=',$uid];
            }
        }
        
        $keyword=isset($data['keyword']) ? $data['keyword']: '';
        if($keyword!=''){
            $map[]=['name','like','%'.$keyword.'%'];
        }
			

    	$lists = Db::name("shop_goods")
                ->where($map)
                ->order("id DESC")
                ->paginate(20);
        
        $lists->each(function($v,$k){
			$v['userinfo']=getUserInfo($v['uid']);
			$oneclass_name=Db::name("shop_goods_class")->where("gc_id={$v['one_classid']}")->value("gc_name");
			$twoclass_name=Db::name("shop_goods_class")->where("gc_id={$v['two_classid']}")->value("gc_name");
			$threeclass_name=Db::name("shop_goods_class")->where("gc_id={$v['three_classid']}")->value("gc_name");

			$v['oneclass_name']=isset($oneclass_name)?$oneclass_name:'';
			$v['twoclass_name']=isset($twoclass_name)?$twoclass_name:'';
			$v['threeclass_name']=isset($threeclass_name)?$threeclass_name:'';

			$thumb_arr=explode(',',$v['thumbs']);
			$v['thumb']=get_upload_path($thumb_arr[0]);

            return $v;           
        });
        
        $lists->appends($data);
        $page = $lists->render();

    	$this->assign('lists', $lists);

    	$this->assign("page", $page);
        
    	$this->assign('status', $this->getStatus());
    	
    	return $this->fetch();
    }
    

    //上架/下架
    function setStatus(){

        $data = $this->request->param();
        $status=$data['status'];
        
        if(isset($data['id'])){

        	$id=$data['id'];

            $goodsinfo=DB::name('shop_goods')->where("id={$id}")->find();

        	$rs = DB::name('shop_goods')->where("id={$id}")->update(['status'=>$status]);
	        if(!$rs){
	            $this->error("操作失败！");
	        }

            if($status==-2){
                $title='你的商品“'.$goodsinfo['name'].'”已被平台下架。';
            }else{
                $title='你的商品“'.$goodsinfo['name'].'”已被平台上架成功。';
            }

            //写入记录表
            
            $id=addSysytemInfo($goodsinfo['uid'],$title,1);

            //发送推送
            jPush($goodsinfo['uid'],$title);



        }else if(isset($data['ids'])){

        	$ids = $data['ids'];
        	$rs = DB::name('shop_goods')->where('id', 'in', $ids)->update(['status'=>$status]);
            if(!$rs){
                $this->error("操作失败！");
            }

            foreach ($ids as $k => $v) {
                //获取商品信息
                $goodsinfo=DB::name('shop_goods')->where("id={$v}")->find();

                if($status==-2){
                    $title='你的商品“'.$goodsinfo['name'].'”已被平台下架。';
                }else{
                    $title='你的商品“'.$goodsinfo['name'].'”已被平台上架成功。';
                }
                
                //写入记录表

                $id=addSysytemInfo($goodsinfo['uid'],$title,1);

                //发送推送
                jPush($goodsinfo['uid'],$title);

            }

        }
        
        $this->success("操作成功！");
    }
    
    
    function setRecom(){
        
        $id = $this->request->param('id', 0, 'intval');
        $isrecom = $this->request->param('isrecom', 0, 'intval');
        
        $rs = DB::name('shop_goods')->where("id={$id}")->update(['isrecom'=>$isrecom]);
        if(!$rs){
            $this->error("操作失败！");
        }
        
        $this->success("操作成功！");
    }
		
    function del(){

    	$data=$this->request->param();

    	if(isset($data['id'])){

    		$id = $data['id'];

            $goodsinfo=DB::name('shop_goods')->where("id={$id}")->find();

	        $rs = DB::name('shop_goods')->where("id={$id}")->delete();
	        if(!$rs){
	            $this->error("删除失败！");
	        }

            $title='你的商品“'.$goodsinfo['name'].'”已被平台删除。';
            //写入记录
            $id=addSysytemInfo($goodsinfo['uid'],$title,1);
            jPush($goodsinfo['uid'],$title);

            //删除商品访问记录
            Db::name("user_goods_visit")->where("goodsid={$id}")->delete();

            //修改视频的绑定信息
            Db::name("video")->where("type=1 and goodsid={$id}")->update(array('type'=>0,'goodsid'=>0));

    	}else if(isset($data['ids'])){
    		$ids=$data['ids'];
    		$rs = DB::name('shop_goods')->where("id",'in',$ids)->delete();
	        if(!$rs){
	            $this->error("删除失败！");
	        }

            foreach ($ids as $k => $v) {
                $goodsinfo=DB::name('shop_goods')->where("id={$v}")->find();
                $title='你的商品“'.$goodsinfo['name'].'”已被平台删除。';
                
                //写入记录
                $id=addSysytemInfo($goodsinfo['uid'],$title,1);

                jPush($goodsinfo['uid'],$title);

                //删除商品访问记录
                Db::name("user_goods_visit")->where("goodsid={$v}")->delete();

                //修改视频的绑定信息
                Db::name("video")->where("type=1 and goodsid={$v}")->update(array('type'=>0,'goodsid'=>0));

            }
    	}

        
        
        $this->success("删除成功！",url("shopgoods/index"));
    }

    //审核/详情
    function edit(){
    	$id = $this->request->param('id', 0, 'intval');

    	$goodsinfo=Db::name("shop_goods")->where("id={$id}")->find();
 

    	if(!$goodsinfo){
    		$this->error("数据错误");
    	}

    	$userinfo=getUserInfo($goodsinfo['uid']);

    	$goodsinfo['userinfo']=$userinfo;

    	$oneclass_name=Db::name("shop_goods_class")->where("gc_id={$goodsinfo['one_classid']}")->value("gc_name");
		$twoclass_name=Db::name("shop_goods_class")->where("gc_id={$goodsinfo['two_classid']}")->value("gc_name");
		$threeclass_name=Db::name("shop_goods_class")->where("gc_id={$goodsinfo['three_classid']}")->value("gc_name");

		$goodsinfo['oneclass_name']=isset($oneclass_name)?$oneclass_name:'';
		$goodsinfo['twoclass_name']=isset($twoclass_name)?$twoclass_name:'';
		$goodsinfo['threeclass_name']=isset($threeclass_name)?$threeclass_name:'';

    	if(isset($goodsinfo['video_url'])){
    		$goodsinfo['video_url']=get_upload_path($goodsinfo['video_url']);
    	}

    	$thumb_arr=explode(',',$goodsinfo['thumbs']);
    	foreach ($thumb_arr as $k => $v) {
    		$thumb_arr[$k]=get_upload_path($v);
    	}

    	$goodsinfo['thumb_arr']=$thumb_arr;
    	$picture_arr=[];

    	if(isset($goodsinfo['pictures'])){
    		$picture_arr=explode(',',$goodsinfo['pictures']);

    		foreach ($picture_arr as $k => $v) {
    			$picture_arr[$k]=get_upload_path($v);
    		}
    	}

    	$goodsinfo['picture_arr']=$picture_arr;

    	$spec_arr=json_decode($goodsinfo['specs'],true);
    	
    	$goodsinfo['spec_arr']=$spec_arr;

    	unset($goodsinfo['thumbs'],$goodsinfo['pictures'],$goodsinfo['specs']);

    	$this->assign('data', $goodsinfo);
        $status=$this->getStatus();
        unset($status['-2']);
        unset($status['-1']);
    	$this->assign('status', $status);
    	
    	return $this->fetch();
    }

    public function editPost(){
    	$data=$this->request->param();

        $id=$data['id'];
        $goodsinfo=Db::name("shop_goods")->where("id={$id}")->find();
        $status=$data['status'];
        $refuse_reason=trim($data['refuse_reason']);

        if($status==2){ //管理员拒绝
            if($refuse_reason==''){
                $this->error("请填写拒绝理由");
            }
        }else{
            $refuse_reason='';
        }

        $data['refuse_reason']=$refuse_reason;

    	$data['uptime']=time();

    	$res=Db::name("shop_goods")->update($data);

        if($res===false){
            $this->error("编辑失败");
        }

        $uid=$goodsinfo['uid'];
        $title='';

        if($status==1){
            $title='你的商品“'.$goodsinfo['name'].'”已通过平台审核,上架成功。';
            jPush($uid,$title);
        }

        if($status==2){
            //发送极光推送
            
            $title='你的商品“'.$goodsinfo['name'].'”未通过平台审核。';
            if(!$refuse_reason){
                $title.='原因：'.$refuse_reason;
            }
            jPush($uid,$title);
        }

        //写入记录
        $id=addSysytemInfo($uid,$title,1);

    	$this->success("修改成功！");
    }

    //商品评论列表
    public function commentlist(){
        $data = $this->request->param();
        $goods_id=$data['goods_id'];
        $map=[];

        $map[]=['goodsid','=',$goods_id];
        $map[]=['is_append','=','0'];
        
        $start_time=isset($data['start_time']) ? $data['start_time']: '';
        $end_time=isset($data['end_time']) ? $data['end_time']: '';
        
        if($start_time!=""){
           $map[]=['addtime','>=',strtotime($start_time)];
        }

        if($end_time!=""){
           $map[]=['addtime','<=',strtotime($end_time) + 60*60*24];
        }

        
        $keyword=isset($data['keyword']) ? $data['keyword']: '';
        if($keyword!=''){
            $map[]=['content','like','%'.$keyword.'%'];
        }
            

        $lists = Db::name("shop_order_comments")
                ->where($map)
                ->order("id asc")
                ->paginate(20);
        
        $lists->each(function($v,$k){
            $v['userinfo']=getUserInfo($v['uid']);
            $v['shop_userinfo']=getUserInfo($v['shop_uid']);
            
            if($v['thumbs']){
                $thumb_arr=explode(',',$v['thumbs']);
                foreach ($thumb_arr as $k1 => $v1) {
                    $thumb_arr[$k1]=get_upload_path($v1);
                }
                $v['thumb_arr']=$thumb_arr;
            }else{
                $v['thumb_arr']=[];
            }
            if($v['video_thumb']){
                $v['video_thumb']=get_upload_path($v['video_thumb']);
            }

            if($v['video_url']){
                $v['video_url']=get_upload_path($v['video_url']);
            }
            
            

            //获取追评信息
            $append_comment=Db::name("shop_order_comments")->where("orderid={$v['orderid']} and is_append=1")->find();

            if($append_comment){

                $append_comment['userinfo']=getUserInfo($append_comment['uid']);
                $append_comment['shop_userinfo']=getUserInfo($append_comment['shop_uid']);

                if($append_comment['thumbs']){
                    $thumb_arr=explode(',',$append_comment['thumbs']);
                    foreach ($thumb_arr as $k1 => $v1) {
                        $thumb_arr[$k1]=get_upload_path($v1);
                    }
                    $append_comment['thumb_arr']=$thumb_arr;
                }else{
                   $append_comment['thumb_arr']=[];
                }

                if($append_comment['video_thumb']){
                    $append_comment['video_thumb']=get_upload_path($append_comment['video_thumb']);
                }

                if($append_comment['video_url']){
                    $append_comment['video_url']=get_upload_path($append_comment['video_url']);
                }

            }
            


            $v['append_comment']=$append_comment;


            return $v;           
        });
        
        $lists->appends($data);
        $page = $lists->render();

        $this->assign('lists', $lists);
        $this->assign('goods_id', $goods_id);

        $this->assign("page", $page);
        
        return $this->fetch();
    }

    //删除视频评论
    function delComment(){
        $id = $this->request->param('id', 0, 'intval');
        $rs=Db::name("shop_order_comments")->where("id={$id}")->delete();
        if(!$rs){
            $this->error("评论删除失败");
        }

        $this->success("删除成功");
    }

    //评论视频播放
    function videoplay(){
        $data=$this->request->param();
        $url=$data['url'];
        $this->assign('url',$url);

        return $this->fetch();
    }
    
}
