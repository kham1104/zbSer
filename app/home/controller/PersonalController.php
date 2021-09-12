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
use cmf\lib\Upload;

class PersonalController extends HomebaseController {
    protected function initialize(){
        parent::initialize();
        
        $personal='';
        $this->assign("personal",$personal);
        
    }
  /**个人中心-首页方法**/
	public function index() {
		LogIn();
		$uid=session("uid");
		$info=getUserPrivateInfo($uid);	
		$this->assign("info",$info);
		$getgif=getgif($uid);
		$this->assign("getgif",$getgif[0]);

		return $this->fetch();
    }
	/**个人中心-头部修改昵称**/
	public function edit_name()
	{
		
		$uid=(int)session("uid");
        
        $data = $this->request->param();
        $name=isset($data['name']) ? $data['name']: '';
        $name=checkNull($name);
        
        if($uid<1){
            echo '{"state":"1"}';
			exit;            
        }
        $where['id']=$uid;
		$userinfo= Db::name("user")->where($where)->setField("user_nicename", $name);
		
		if($userinfo)
		{
            $_SESSION['user']['user_nicename']=  $name;
			echo '{"state":"0"}';
		}
		else
		{
			echo '{"state":"1"}';
		}
		exit;
	}
	 /**
	个人中心-基本资料展示
	**/
	public function modify() 
	{
		LogIn();
		$uid=session("uid");
		$info=getUserPrivateInfo($uid);	
        
        
		$this->assign("info",$info);
		$this->assign("personal",'Set');
		return $this->fetch();
    }
	 /**
	个人中心-基本资料修改
	**/
	public function edit_modify()
   {
	  
	  $uid=(int)session("uid");
	  $token=session("token");
		$checkToken=checkToken($uid,$token);
		if($checkToken==700)
		{
			echo '{"state":"0","msg":"登录失效,请重新登录"}';
			exit;
		}
        
        $data = $this->request->param();
        $birthday=isset($data['birthday']) ? $data['birthday']: '';
        $birthday=checkNull($birthday);
        
        $user_nicename=isset($data['nickName']) ? $data['nickName']: '';
        $user_nicename=checkNull($user_nicename);
        
        $sex=isset($data['sex']) ? $data['sex']: '';
        $sex=(int)checkNull($sex);
        
        $signature=isset($data['signature']) ? $data['signature']: '';
        $signature=checkNull($signature);
        
        
		 $up=array(
			"user_nicename"=>$user_nicename,
			"sex"=> $sex,
			"signature"=>$signature
		 );
         
         if($birthday){
             $birthday=strtotime($birthday);
             $up['birthday']=$birthday;
         }
		$result=Db::name("user")->where(['id'=>$uid])->update($up);
		if($result===false)
		{
            echo '{"state":"1","msg":"修改失败"}';
			exit;
		}
        
        $_SESSION['user']['user_nicename']= $user_nicename;
        $_SESSION['user']['sex']= $sex;
        $_SESSION['user']['signature']= $signature;
        echo '{"state":"0","msg":"修改成功"}';
		exit;
   }
    /**
	个人中心-头像展示
	**/
	public function photo()
	{
		LogIn();
		$uid=session("uid");
		$info= Db::name("user")->field('id,user_login,user_nicename,avatar,avatar_thumb')->where(['id'=>$uid])->find();
        if($info){
            $info['avatar_s']=$info['avatar'];
            $info['avatar']=get_upload_path($info['avatar']);
            $info['avatar_thumb_s']=$info['avatar_thumb'];
            $info['avatar_thumb']=get_upload_path($info['avatar_thumb']);
        }
		$this->assign("info",$info);
		$this->assign("personal",'Set');
		return $this->fetch();
	}
	/**个人中心-修改头像**/
	public function edit_photo()
	{
		
		$uid=session("uid");
		$token=session("token");
		$checkToken=checkToken($uid,$token);
		if($checkToken==700)
		{
			$callback = array(
				'error' => 0,
				'type'  => "登录失效,请重新登录"
				);
			echo json_encode($callback);
			exit;
		}
        
        $data = $this->request->param();
        $url=isset($data['avatar']) ? $data['avatar']: '';
        $url=checkNull($url);
        
        
		if (empty($url)) {
            $callback = array(
				'error' => 0,
				'type'  => "图片处理失败"
			);
            echo json_encode($callback);
            exit;
        }
        
        $avatar=  $url.'?imageView2/2/w/600/h/600'; //600 X 600
        $avatar_thumb=  $url.'?imageView2/2/w/200/h/200'; // 200 X 200
        
        $data=array(
            "avatar"=>$avatar,
            "avatar_thumb"=>$avatar_thumb,
        );
        
        $result=Db::name('user')->where(['id'=>$uid])->update($data); 
        
        if($result===false)
        {
            $callback = array(
                'error' => 0,
                'type'  => "头像修改失败"
            );
            echo json_encode($callback);
            exit;
        }
        
        $_SESSION['user']['avatar']=$avatar;
        $_SESSION['user']['avatar_thumb']=$avatar_thumb;
        $callback = array(
            'error' => 1,
            'type'  => "头像修改成功"
            );
		echo json_encode($callback);
		exit;
	}
	/**个人中心-我的认证**/
	public function card()
	{
		LogIn();
		$uid=(int)session("uid");
		$this->assign("uid",$uid);
        $where['uid']=$uid;
		$auth= Db::name("user_auth")->where($where)->find();
		$info=getUserPrivateInfo($uid);	
		$this->assign("info",$info);
		$this->assign("auth",$auth);
		$this->assign("personal",'card');
		return $this->fetch();
	}
	/**
	个人中心-我的认证-身份证上传
	$info判断上传状态
	**/
	function upload(){
        
        $uploader = new Upload();
        $uploader->setFileType('image');
        $result = $uploader->upload();

        if ($result === false) {
            
            echo json_encode(array("ret"=>0,'file'=>'','msg'=>$uploader->getError()));
            exit;
        }
        
        /* $result=[
            'filepath'    => $arrInfo["file_path"],
            "name"        => $arrInfo["filename"],
            'id'          => $strId,
            'preview_url' => cmf_get_root() . '/upload/' . $arrInfo["file_path"],
            'url'         => cmf_get_root() . '/upload/' . $arrInfo["file_path"],
        ]; */
        
        echo json_encode(array("ret"=>200,'data'=>array("url"=>$result['url']),'msg'=>''));
        exit;
        
	}
	/**
	个人中心-我的认证-认证信息写入数据库
	**/
	function authsave()
	{ 
		$uid=(int)session("uid");
		
        
        $data = $this->request->param();
        $real_name=isset($data['real_name']) ? $data['real_name']: '';
        $real_name=checkNull($real_name);
        
        $mobile=isset($data['mobile']) ? $data['mobile']: '';
        $mobile=checkNull($mobile);
        
        $cer_no=isset($data['cer_no']) ? $data['cer_no']: '';
        $cer_no=checkNull($cer_no);
        
        $front_view=isset($data['front_view']) ? $data['front_view']: '';
        $front_view=checkNull($front_view);
        
        $back_view=isset($data['back_view']) ? $data['back_view']: '';
        $back_view=checkNull($back_view);
        
        $handset_view=isset($data['handset_view']) ? $data['handset_view']: '';
        $handset_view=checkNull($handset_view);
        
        
        if($uid<1){
            echo json_encode(array("ret"=>0,'data'=>array(),'msg'=>'参数错误'));
			exit;            
        }
        
        if($real_name==''){
            echo json_encode(array("ret"=>0,'data'=>array(),'msg'=>'请填写您的真实姓名'));
			exit;
        }
        
        if($mobile==''){
            echo json_encode(array("ret"=>0,'data'=>array(),'msg'=>'请填写您的手机号'));
			exit;
        }
        
        if($cer_no==''){
            echo json_encode(array("ret"=>0,'data'=>array(),'msg'=>'请填写您的身份证号'));
			exit;
        }
        
        if($front_view==''){
            echo json_encode(array("ret"=>0,'data'=>array(),'msg'=>'请上传证件相关照片'));
			exit;
        }
        
        if($back_view==''){
            echo json_encode(array("ret"=>0,'data'=>array(),'msg'=>'请上传证件相关照片'));
			exit;
        }
        
        if($handset_view==''){
            echo json_encode(array("ret"=>0,'data'=>array(),'msg'=>'请上传证件相关照片'));
			exit;
        }
        
        $info=[
            'uid'=>$uid,
            'real_name'=>$real_name,
            'mobile'=>$mobile,
            'cer_no'=>$cer_no,
            'front_view'=>$front_view,
            'back_view'=>$back_view,
            'handset_view'=>$handset_view,
            'status'=>0,
            'addtime'=>time(),
        ];
        
		$result=Db::name("user_auth")->where("uid='{$uid}'")->update($info);
		if(!$result)
		{
			$result=Db::name("user_auth")->insert($info);
		}
        
        if($result===false)
		{		
			echo json_encode(array("ret"=>0,'data'=>array(),'msg'=>'提交失败，请重新提交'));
            exit;
		}
			
        echo json_encode(array("ret"=>200,'data'=>array(),'msg'=>''));
		exit;		
	}	
	/**
	个人中心-我关注的
	**/
  public function follow()
	{
		LogIn();
		$uid=(int)session("uid");
		$info=getUserPrivateInfo($uid);	
		$this->assign("info",$info);
        $where['uid']=$uid;
		$attention=Db::name("user_attention")->where($where)->select()->toArray();
		foreach($attention as $k=>$v)
		{
			$users=getUserInfo($v['touid']);
			$attention[$k]['users']=$users;
            $attention[$k]['follow']=getFollownums($v['touid']);
            $attention[$k]['fans']=getFansnums($v['touid']);
		}
		$this->assign("attention",$attention);
		$this->assign("personal",'follow');
		return $this->fetch();
	}
	/**
	个人中心-我关注的-取消关注
	**/
	public function follow_dal()
	{
		
		$uid=(int)session("uid");
        
        $data = $this->request->param();
        $followID=isset($data['followID']) ? $data['followID']: '';
        $touid=(int)checkNull($followID);
        
        if($uid<1 || $touid<1){
			echo '{"state":"1","msg":"参数错误"}';
			exit;            
        }
        $where['touid']=$touid;
        $where['uid']=$uid;
        
		$del_follow=Db::name("user_attention")->where($where)->delete();
		if($del_follow===false)
		{
            echo '{"state":"1","msg":"取消失败"}';
            exit;
			
		}
        echo '{"state":"0","msg":"取消关注"}';
        exit;
	}
	public function follow_add()
	{
		
		$uid=(int)session("uid");
        
        $data = $this->request->param();
        $touid=isset($data['touid']) ? $data['touid']: '';
        $touid=(int)checkNull($touid);
        
        if($uid<1 || $touid<1){
			echo '{"state":"1","msg":"参数错误"}';
			exit;            
        }
		$data=array(
			"uid"=>$uid,
			"touid"=>$touid
		);
		$result=Db::name("user_attention")->insert($data);
		if($result!==false)
		{
			echo '{"state":"1","msg":"关注失败"}';
            exit;
		}
        
        Db::name("user_black")->where($data)->delete();
        echo '{"state":"0","msg":"关注成功"}';
        exit;
	}
	/**
	个人中心-我的粉丝
	**/
	public function fans()
	{
		LogIn();
		$uid=(int)session("uid");
		$info=getUserPrivateInfo($uid);	
		$this->assign("info",$info);
        
        $where['touid']=$uid;
        
		$attention=Db::name("user_attention")->where($where)->select()->toArray();
		foreach($attention as $k=>$v)
		{
			$users=getUserInfo($v['uid']);
			$attention[$k]['users']=$users;
      		$attention[$k]['follow']=getFollownums($v['uid']);
      		$attention[$k]['fans']=getFansnums($v['uid']);
			$isAttention=isAttention($uid,$v['uid']);
			$attention[$k]['attention']=$isAttention;
			$attention[$k]['isblack']=isBlack($uid,$v['uid']);
			
		}
		$this->assign("attention",$attention);
		$this->assign("personal",'follow');
		return $this->fetch();
	}

	/*黑名单*/
	public function namelist()
	{
		LogIn();
		$uid=(int)session("uid");
		$info=getUserPrivateInfo(session("uid"));	
		$this->assign("info",$info);
        
        $where['uid']=$uid;
        
		$attention=Db::name("user_black")->where($where)->select()->toArray();
		foreach($attention as $k=>$v)
		{
			$users=getUserInfo($v['touid']);
			$attention[$k]['users']=$users;
            $attention[$k]['follow']=getFollownums($v['touid']);
            $attention[$k]['fans']=getFansnums($v['touid']);
			$isAttention=isAttention($uid,$v['touid']);
			$attention[$k]['attention']=$isAttention;
		}
		$this->assign("attention",$attention);
		$this->assign("personal",'follow');
		return $this->fetch();
	}
	/*删除黑名单*/
	public function list_del()
	{
		$uid=(int)session("uid");
		$data = $this->request->param();
        $touid=isset($data['touid']) ? $data['touid']: '';
        $touid=(int)checkNull($touid);
        
        if($uid<1 || $touid<1){
            echo '{"state":"1000","msg":"参数错误"}';
			exit;            
        }
        
		$isBlack=isBlack($uid,$touid);
		if($isBlack==0)
		{
			echo '{"state":"1000","msg":"该用户不在你的黑名单内"}';
			exit;
		}

        $where['touid']=$touid;
        $where['uid']=$uid;
        
        $attention=Db::name("user_black")->where($where)->delete();
        if($attention===false)
        {
            echo '{"state":"1001","msg":"移除失败"}';
            exit;
            
        }
        
        echo '{"state":"0","msg":"移除成功"}';
        exit;
	}
	/*拉黑操作 如果我已经关注这个主播 同时会删除关注状态但是不会清除粉丝*/

	public function blacklist(){
		$uid=(int)session("uid");
        
		$data = $this->request->param();
        $touid=isset($data['touid']) ? $data['touid']: '';
        $touid=(int)checkNull($touid);
        
        if($uid<1 || $touid<1){
            echo '{"state":"1000","msg":"参数错误"}';
			exit;            
        }
        
        
		$isBlack=isBlack($uid,$touid);
		if($isBlack==1)
		{
			echo '{"state":"1000","msg":"你已经将该用户拉黑"}';
			exit;
		}
        
        $isAttention=isAttention($uid,$touid);
        if($isAttention)
        {
            $where['touid']=$touid;
            $where['uid']=$uid;
            
            Db::name('user_attention')->where($where)->delete();
        }
        
        $data=array(
            "uid"=>$uid,
            "touid"=>$touid
        );


        $result=Db::name('user_black')->insert($data);

        if(!$result)
        {
            echo '{"state":"1001","msg":"拉黑失败"}';
            exit;
        }
        
        echo '{"state":"0","msg":"拉黑成功"}';
        exit;
	}
	/**
	个人中心-管理员管理中心
	**/
	public function admin()
	{
		LogIn();
		$uid=(int)session("uid");
		$info=getUserPrivateInfo($uid);	
		$this->assign("info",$info);
        
        $where['liveuid']=$uid;
        
		$admin=Db::name("live_manager")->where($where)->select()->toArray();
		foreach($admin as $k=>$v)
		{
			$users=getUserInfo($v['uid']);
			$admin[$k]['users']=$users;
            $admin[$k]['follow']=getFollownums($v['uid']);
            $admin[$k]['fans']=getFansnums($v['uid']);
			$isAttention=isAttention($uid,$v['uid']);
			$admin[$k]['attention']=$isAttention;
		}
		$this->assign("admin",$admin);
		$this->assign("personal",'follow');
		return $this->fetch();
	}
	/**
	个人中心-管理员管理中心-取消管理员
	live_manager管理员记录表
	**/
	function admin_del()
	{ 
		$uid=(int)session("uid");
        
        $data = $this->request->param();
        $touid=isset($data['touid']) ? $data['touid']: '';
        $touid=(int)checkNull($touid);
        
        if($uid<1 || $touid<1){
            echo '{"state":"1000","msg":"参数错误"}';
			exit;            
        }
        
        $where['uid']=$touid;
        $where['liveuid']=$uid;
        
        $rst = Db::name("live_manager")->where($where)->delete();
        if (!$rst){
            echo '{"state":"1000","msg":"管理取消失败"}';
            exit;
        }
        
        echo '{"state":"0","msg":"管理取消成功"}';
        exit;
  }
	/**
	个人中心-提现中心
	
	**/
	public function exchange()
	{
		LogIn();
		$uid=(int)session("uid");
		$token=session("token");
		$info=getUserPrivateInfo($uid);	

		$config=getConfigPri();
		//提现比例
		$cash_rate=$config['cash_rate'];
		$cash_start=$config['cash_start'];
		$cash_end=$config['cash_end'];
		$cash_max_times=$config['cash_max_times'];
		//剩余票数
		$votes=$info['votes'];
		$votestotal=$info['votestotal'];
			
		//总可提现数
		$total=floor($votes/$cash_rate);
        
        if($cash_max_times){
            //$tips='每月'.$cash_start.'-'.$cash_end.'号可进行提现申请，收益将在'.($cash_end+1).'-'.($cash_end+5).'号统一发放，每月只可提现'.$cash_max_times.'次';
            $tips='每月'.$cash_start.'-'.$cash_end.'号可进行提现申请，每月只可提现'.$cash_max_times.'次';
        }else{
            //$tips='每月'.$cash_start.'-'.$cash_end.'号可进行提现申请，收益将在'.($cash_end+1).'-'.($cash_end+5).'号统一发放';
            $tips='每月'.$cash_start.'-'.$cash_end.'号可进行提现申请';
        }
		
        
		$rs=array(
			"votes"=>$votes,
			"votestotal"=>$votestotal,
			"todaycash"=>$votes,
			"total"=>$total,
			"cash_rate"=>$cash_rate,
			"tips"=>$tips,
		);
		$zlist=Db::name('cash_account')->where("uid={$uid}")
                ->order("id desc")
                ->select()
                ->toArray();
		$type=array(
			'1'=>"支付宝",
			'2'=>"微信",
			'3'=>"银行卡",
		);
		foreach($zlist as $k=>$v){
			$zlist[$k]['type_account']=$type[$v['type']]."-".$v['account'];
		}
		$this->assign("token",$token);
		
		$this->assign("uid",$uid);
	 	$this->assign("zlist",$zlist);
	 	$this->assign("info",$info);
	 	$this->assign("rs",$rs);
		$this->assign("personal",'card');
		return $this->fetch();
	}
	/**
	个人中心-提现中心开始提现
	**/
	public function edit_exchange(){
		
		
		$uid=(int)session("uid");
		$token=session("token");
		$checkToken=checkToken($uid,$token);
		if($checkToken==700)
		{
			echo '{"code":"1003","msg":"您的登陆状态失效，请重新登陆！"}';
			exit;
		}
		
        $where['uid']=$uid;
        
		$isrz=Db::name("user_auth")->field("status")->where($where)->find();
		if(!$isrz || $isrz['status']!=1){
			echo '{"code":"1003","msg":"请先进行身份认证"}';
			exit;
		}
		
		$info=getUserPrivateInfo($uid);	
		
		$nowtime=time();
        $data = $this->request->param();
        $accountid=isset($data['accountid']) ? $data['accountid']: '';
        $accountid=(int)checkNull($accountid);
        
        $cashvote=isset($data['cashvote']) ? $data['cashvote']: '';
        $cashvote=(int)checkNull($cashvote);
        
        
        if($accountid <1 || $cashvote<=0){
            echo '{"code":"1001","msg":"信息错误"}';
			exit;
        }
        
        $config=getConfigPri();
        $cash_start=$config['cash_start'];
        $cash_end=$config['cash_end'];
        $cash_max_times=$config['cash_max_times'];
        
        $day=(int)date("d",$nowtime);
        if($day < $cash_start || $day > $cash_end){
            echo '{"code":"1005","msg":"不在提现期限内，不能提现"}';
			exit;
        }
        
        //本月第一天
        $month=date('Y-m-d',strtotime(date("Ym",$nowtime).'01'));
        $month_start=strtotime(date("Ym",$nowtime).'01');

        //本月最后一天
        $month_end=strtotime("{$month} +1 month");      

        if($cash_max_times){
            $isexist=Db::name('cash_record')
                    ->where("uid={$uid} and addtime > {$month_start} and addtime < {$month_end}")
                    ->count();
            if($isexist > $cash_max_times){
                echo '{"code":"1006","msg":"每月只可提现'.$cash_max_times.'次,已达上限"}';
                exit;
            }   
        }

		
        /* 钱包信息 */
		$accountinfo=Db::name('cash_account')
				->where("id={$accountid}")
				->find();
        if(!$accountinfo){
            echo '{"code":"1006","msg":"该钱包不存在"}';
			exit;
        }
		
		$votes=$info['votes'];
        
        if($cashvote > $votes){
            echo '{"code":"1001","msg":"余额不足"}';
			exit;
        }
        

		//提现比例
		$cash_rate=$config['cash_rate'];
		/* 最低额度 */
		$cash_min=$config['cash_min'];
		
		//提现钱数
        $money=floor($cashvote/$cash_rate);
		
		if($money < $cash_min){
            echo '{"code":"1001","msg":"提现最低额度为'.$cash_min.'元"}';
			exit;
		}
		
		$cashvotes=$money*$cash_rate;
		$data=array(
			"uid"=>$uid,
			"money"=>$money,
			"votes"=>$cashvotes,
			"orderno"=>$uid.'_'.$nowtime.rand(100,999),
			"status"=>0,
			"addtime"=>$nowtime,
			"uptime"=>$nowtime,
			"type"=>$accountinfo['type'],
			"account_bank"=>$accountinfo['account_bank'],
			"account"=>$accountinfo['account'],
			"name"=>$accountinfo['name'],
		);
		
		$rs=Db::name("cash_record")->insert($data);
		if(!$rs){
			echo '{"code":"1002","msg":"提现失败，请重试"}';
			exit;
		}
        
        Db::name("user")->where("id={$uid}")->setDec('votes',$cashvotes); 
        echo '{"code":"0","msg":"提现成功"}';
        exit;
	}
	
	/* 提现记录*/
	var $status=array(
        '0'=>'审核中',
        '1'=>'成功',
        '2'=>'失败',
    );
	public function cash_list(){
        LogIn();
        
		$uid=(int)session("uid");
		
		$info=getUserPrivateInfo($uid);	
		$pagesize = 20; 
        $where['uid']=$uid;
		
		$list=Db::name("cash_record")
			->where($where)
			->order("id desc")
			->paginate(20);
            
        $list->each(function($v,$k){
            $v['addtime']=date('Y.m.d',$v['addtime']);
            $v['status_name']=$this->status[$v['status']];
            return $v; 
        });
        
        $page = $list->render();

    	$this->assign('list', $list);

    	$this->assign("page", $page);
        
		$this->assign("info",$info);

		return $this->fetch();
	}
	
	
		/**
	个人中心-账号管理
	
	**/
	public function account_list()
	{
		LogIn();
		$uid=(int)session("uid");
		$token=session("token");
		$pagesize = 20; 
		$info=getUserPrivateInfo($uid);	
        $where['uid']=$uid;
		
        
		$list=Db::name('cash_account')
				->where($where)
                ->order("id desc")
			   ->paginate(20);
		
        $list->each(function($v,$k){
            
            if($v['type']==1){
				$v['type_account']="支付宝";
				$v['account_bank']="-----";
			}else if($v['type']==2){
				$v['type_account']="微信";
				$v['account_bank']="-----";
				$v['name']="-----";
			}else if($v['type']==3){
				$v['type_account']="银行卡";
			}
            return $v; 
        });
        
        
        $page = $list->render();

    	$this->assign('list', $list);

    	$this->assign("page", $page);
        
        
		$this->assign("token",$token);
		
		$this->assign("uid",$uid);
		$this->assign("info",$info);
		return $this->fetch();
	}
	/*修改密码*/
	public function updatepass(){
		LogIn();
        $uid=(int)session("uid");
		$info=getUserPrivateInfo($uid);	
		$this->assign("info",$info);
		$this->assign("personal",'Set');
		return $this->fetch();
	}
	/* 执行密码修改 */
	public function savepass() {
		$uid=(int)session("uid");
        
        $data = $this->request->param();
        $oldpass=isset($data['oldpass']) ? $data['oldpass']: '';
        $oldpass=checkNull($oldpass);
        
        $newpass=isset($data['newpass']) ? $data['newpass']: '';
        $newpass=checkNull($newpass);
        
        $repass=isset($data['repass']) ? $data['repass']: '';
        $repass=checkNull($repass);
        
        if($oldpass==''){
            $rs['code'] = 1001;
			$rs['msg'] = '请输入旧密码';
			echo json_encode($rs);
			exit;	
        }
        
        if($newpass==''){
            $rs['code'] = 1001;
			$rs['msg'] = '请输入新密码';
			echo json_encode($rs);
			exit;	
        }
        
		$rs=array();
		if($newpass !== $repass)
		{
			$rs['code'] = 800;
			$rs['msg'] = '两次密码不一致';
			echo json_encode($rs);
			exit;
		}
		
		$check =passcheck($newpass); 
		if(!$check)
		{
			$rs['code'] = 1001;
			$rs['msg'] = '密码为6-20位数字与字母组合';
			echo json_encode($rs);
			exit;			
		}
        
        $oldpass = cmf_password($oldpass);
		/* 密码判定 */
        $where['id']=$uid;
        $where['user_type']=2;
        
		$rt=Db::name("user")->where($where)->value('user_pass');
		if(!$rt || $rt!=$oldpass){
			$rs['code'] = 103;
			$rs['msg'] = '旧密码错误';
			echo json_encode($rs);
			exit;	
		}
        
        
		$pwd = cmf_password($newpass);

		$map['id'] =$uid;
		//保存昵称到数据库
		$result=Db::name("user")->where($map)->save('user_pass',$pwd);
		if($result===false){
			$rs['code'] = 1005;
			$rs['msg'] = '修改失败';
			echo json_encode($rs);
			exit;
		}
		
		$rs['code'] = 0;
        $rs['msg'] = '修改成功';
        echo json_encode($rs);
        exit;
  }
	/**
	个人中心-直播记录
	**/
	public function live()
	{
		$uid=(int)session("uid");
		LogIn();
	 	$where=array();
		$where['uid']=$uid;
        
        $data = $this->request->param();
        $map=[];
        
        $start_time=isset($data['start_time']) ? $data['start_time']: '';
        $end_time=isset($data['end_time']) ? $data['end_time']: '';
        
        if($start_time!=""){
           $map[]=['starttime','>=',strtotime($start_time)];
        }

        if($end_time!=""){
           $map[]=['starttime','<=',strtotime($end_time) + 60*60*24];
        }
        
		
		$info=getUserPrivateInfo(session("uid"));	
		$this->assign("info",$info);
        
        $where2['id']=$uid;
        
		$coin=Db::name('user')->where($where2)->value("coin");
		$this->assign('coin',$coin);
		
		$lists = Db::name('live_record')
                ->where($where)
                ->order("id desc")
                ->paginate(20);
        $lists->each(function($v,$k){
            $v['starttime']=date('Y-m-d H:is',$v['starttime']);
            if($v['endtime']){
                $v['endtime']=date('Y-m-d H:is',$v['endtime']);
            }else{
                $v['endtime']='';
            }
            
            $type=$v['type'];
            $type_s='一般直播';
            switch($type){
                case '3':
                    $type_s='计时直播';
                    break;
                case '2':
                    $type_s='门票直播';
                    break;
                case '1':
                    $type_s='私密直播';
                    break;
                case '0':
                    $type_s='一般直播';
                    break;
                default:
                    $type_s='一般直播';

            }
            $v['type_s']=$type_s;
            
            return $v;
        });
        
        $lists->appends($data);
        $page = $lists->render();

    	$this->assign('lists', $lists);

    	$this->assign("page", $page);
        
		$this->assign('uid',$uid);
		$this->assign("personal",'follow');
		return $this->fetch();
	}	
}


