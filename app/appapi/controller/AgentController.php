<?php
/**
 * 分销
 */
namespace app\appapi\controller;

use cmf\controller\HomeBaseController;
use think\Db;

class AgentController extends HomebaseController {
	
	function index(){       
		$data = $this->request->param();
        $uid=isset($data['uid']) ? $data['uid']: '';
        $token=isset($data['token']) ? $data['token']: '';
        $uid=(int)checkNull($uid);
        $token=checkNull($token);
        
        $checkToken=checkToken($uid,$token);
		if($checkToken==700){
			$reason='您的登陆状态失效，请重新登陆！';
			$this->assign('reason', $reason);
			return $this->fetch(':error');
		}
		  
		$nowtime=time();

		$userinfo=getUserInfo($uid);
		$code=Db::name('agent_code')->where(["uid"=>$uid])->value('code');
		
		if(!$code){
			$code=createCode();
            $ifok=Db::name('agent_code')->where(["uid"=>$uid])->update(array("code"=>$code));
            if(!$ifok){
                Db::name('agent_code')->insert(array('uid'=>$uid,"code"=>$code));
            }
			
		}

		$code_a=str_split($code);

		$this->assign("code",$code);
		$this->assign("code_a",$code_a);
		$agentinfo=array();
        
        /* 是否是分销下级 */
        $users_agent=Db::name("agent")->where(["uid"=>$uid])->find();
		if($users_agent){
			$agentinfo= getUserInfo($users_agent['one_uid']);
		}
		
		
		$agentprofit=Db::name("agent_profit")->where(["uid"=>$uid])->find();
		
		$one_profit=$agentprofit['one_profit'];
		if(!$one_profit){
			$one_profit=0;
		}

		$agnet_profit=array(
			'one_profit'=>number_format($one_profit),
		);

		//统计我的下级人数
        $agentdownsum=Db::name("agent")->where(["one_uid"=>$uid])->count();
        //统计当前分享所得的余额
        $sharebalance = Db::name("user")->where(["id"=>$uid])->value('balance');

		$site_info = getConfigPub();

		$fx_word = $site_info['fx_word'].' '.$site_info['fx_url'].$code;

		$this->assign("uid",$uid);
		$this->assign("fx_word",$fx_word);
		$this->assign("fx_url",$site_info['fx_url'].$code);
		$this->assign("token",$token);
		$this->assign("userinfo",$userinfo);
		$this->assign("agentinfo",$agentinfo);
		$this->assign("agnet_profit",$agnet_profit);
		$this->assign("agentdownsum",$agentdownsum);
		$this->assign("sharebalance",$sharebalance);

		return $this->fetch();
	    
	}

	//余额提现
	function withdraw(){
        $data = $this->request->param();
        $uid=isset($data['uid']) ? $data['uid']: '';
        $token=isset($data['token']) ? $data['token']: '';
        $uid=(int)checkNull($uid);
        $token=checkNull($token);

        $checkToken=checkToken($uid,$token);
        if($checkToken==700){
            $reason='您的登陆状态失效，请重新登陆！';
            $this->assign('reason', $reason);
            return $this->fetch(':error');
        }

        $b=Db::name('user')
            ->field('balance,balance_total')
            ->where('id','=', $uid)
            ->find();

        $this->assign("uid",$uid);
        $this->assign("token",$token);
        $this->assign("balance",$b['balance']);
        $this->assign("balance_total",$b['balance_total']);
	    return  $this->fetch();
    }

    //帐户列表
    function getaccountlist(){
        $rs=array('code'=>0,'info'=>array(),'msg'=>'设置成功');
        $data = $this->request->param();
        $uid=isset($data['uid']) ? $data['uid']: '';
        $uid=(int)checkNull($uid);

        $list=Db::name('cash_account')
            ->where(["uid"=>$uid])
            ->order("addtime desc")
            ->select();

//        return $list;
        $rs['info'] = $list;
        echo json_encode($rs);
        exit;
    }

    //提现
    function withdraw_submit(){
        $rs=array('code'=>0,'info'=>array(),'msg'=>'提现成功,等待审核');
        $data = $this->request->param();
        $uid=(int)checkNull($data['uid']);
        $token=checkNull($data['token']);
        $accountid=checkNull($data['accountid']);
        $money=checkNull($data['money']);
        $time=checkNull($data['time']);

        $checkToken=checkToken($uid,$token);
        if($checkToken==700){
            $reason='您的登陆状态失效，请重新登陆！';
            $this->assign('reason', $reason);
            return $this->fetch(':error');
        }

        if(!$accountid){
            $rs['code'] = 1001;
            $rs['msg'] = '请选择提现账号';
            return $rs;
        }

        if(!$money){
            $rs['code'] = 1002;
            $rs['msg'] = '请输入有效的提现金额';
            return $rs;
        }

        $now=time();
        if($now-$time>300){
            $rs['code']=1001;
            $rs['msg']='参数错误';
            return $rs;
        }

        $configpri=getConfigPri();

        $data=array(
            'uid'=>$uid,
            'accountid'=>$accountid,
            'money'=>$money,
        );

        $res = $this->setShopCash($data);

        if($res==1001){
            $rs['code'] = 1001;
            $rs['msg'] = '余额不足';
            return $rs;
        }else if($res==1004){
            $rs['code'] = 1004;
            $rs['msg'] = '提现最低额度为'.$configpri['balance_cash_min'].'元';
            return $rs;
        }else if($res==1005){
            $rs['code'] = 1005;
            $rs['msg'] = '不在提现期限内，不能提现';
            return $rs;
        }else if($res==1006){
            $rs['code'] = 1006;
            $rs['msg'] = '每月只可提现'.$configpri['balance_cash_max_times'].'次,已达上限';
            return $rs;
        }else if($res==1007){
            $rs['code'] = 1007;
            $rs['msg'] = '提现账号信息不正确';
            return $rs;
        }else if(!$res){
            $rs['code'] = 1002;
            $rs['msg'] = '提现失败，请重试';
            return $rs;
        }

        return $rs;
    }
	
	function agent(){
		$data = $this->request->param();
        $uid=isset($data['uid']) ? $data['uid']: '';
        $token=isset($data['token']) ? $data['token']: '';
        $uid=(int)checkNull($uid);
        $token=checkNull($token);
        
        $checkToken=checkToken($uid,$token);
		if($checkToken==700){
			$reason='您的登陆状态失效，请重新登陆！';
			$this->assign('reason', $reason);
			return $this->fetch(':error');
		}
		
		$agentinfo=array();
		
		$users_agent=Db::name('agent')->where(["uid"=>$uid])->find();
		if($users_agent){
			$agentinfo=getUserInfo($users_agent['one_uid']);
			
			$code=Db::name('agent_code')->where("uid={$users_agent['one_uid']}")->value('code');
			
			$agentinfo['code']=$code;
			$code_a=str_split($code);

			$this->assign("code_a",$code_a);
		}
	
		
		$this->assign("uid",$uid);
		$this->assign("token",$token);

		$this->assign("agentinfo",$agentinfo);

		return $this->fetch();
	}
	
	function setAgent(){
		$data = $this->request->param();
        $uid=isset($data['uid']) ? $data['uid']: '';
        $token=isset($data['token']) ? $data['token']: '';
        $code=isset($data['code']) ? $data['code']: '';
        $uid=(int)checkNull($uid);
        $token=checkNull($token);
        $code=checkNull($code);
		
		$rs=array('code'=>0,'info'=>array(),'msg'=>'设置成功');
		
		if(checkToken($uid,$token)==700){
			$rs['code']=700;
			$rs['msg']='您的登陆状态失效，请重新登陆！';
			echo json_encode($rs);
			exit;
		} 

		if($code==""){
			$rs['code']=1001;
			$rs['msg']='邀请码不能为空';
			echo json_encode($rs);
			exit;
		}
		
		$isexist=Db::name('agent')->where(["uid"=>$uid])->find();
		if($isexist){
			$rs['code']=1001;
			$rs['msg']='已设置';
			echo json_encode($rs);
			exit;
		}
		
		$oneinfo=Db::name('agent_code')->field("uid")->where(["code"=>$code])->find();
		if(!$oneinfo){
			$rs['code']=1002;
			$rs['msg']='邀请码错误';
			echo json_encode($rs);
			exit;
		}
		
		if($oneinfo['uid']==$uid){
			$rs['code']=1003;
			$rs['msg']='不能填写自己的邀请码';
			echo json_encode($rs);
			exit;
		}
		
		$one_agent=Db::name('agent')->where("uid={$oneinfo['uid']}")->find();
		if(!$one_agent){
			$one_agent=array(
				'uid'=>$oneinfo['uid'],
				'one_uid'=>0,
			);
		}else{

			if($one_agent['one_uid']==$uid){
				$rs['code']=1004;
				$rs['msg']='您已经是该用户的上级';
				echo json_encode($rs);
				exit;
			}
		}
		
		$data=array(
			'uid'=>$uid,
			'one_uid'=>$one_agent['uid'],
			'addtime'=>time(),
		);
		Db::name('agent')->insert($data);

		//发放奖励
//        Db::name('user')->where('id',$uid)->setInc('coin',200);


		echo json_encode($rs);
		exit;
	}

	function quit(){
		$data = $this->request->param();
        $uid=isset($data['uid']) ? $data['uid']: '';
        $token=isset($data['token']) ? $data['token']: '';
        $uid=(int)checkNull($uid);
        $token=checkNull($token);
        
        $checkToken=checkToken($uid,$token);
		if($checkToken==700){
			$reason='您的登陆状态失效，请重新登陆！';
			$this->assign('reason', $reason);
			return $this->fetch(':error');
		}
		
		$isexist=Db::name('agent')->where(["uid"=>$uid])->delete();

		echo json_encode($rs);
		exit;
	}
	
	function one(){
		$data = $this->request->param();
        $uid=isset($data['uid']) ? $data['uid']: '';
        $token=isset($data['token']) ? $data['token']: '';
        $uid=(int)checkNull($uid);
        $token=checkNull($token);
		
		if(checkToken($uid,$token)==700){
			$this->assign("reason",'您的登陆状态失效，请重新登陆！');
			$this->display(':error');
			exit;
		} 
		
		$list=Db::name('agent_profit_recode')->field("uid,sum(one_profit) as total")->where(["one_uid"=>$uid])->group("uid")->order("addtime desc")->limit(0,50)->select()->toArray();
		foreach($list as $k=>$v){
			$list[$k]['userinfo']=getUserInfo($v['uid']);
			$list[$k]['total']=NumberFormat($v['total']);
		}
		$this->assign("uid",$uid);
		$this->assign("token",$token);
		$this->assign("list",$list);
		return $this->fetch();
	}

	function one_more(){
		$data = $this->request->param();
        $uid=isset($data['uid']) ? $data['uid']: '';
        $token=isset($data['token']) ? $data['token']: '';
        $p=isset($data['page']) ? $data['page']: '1';
        $uid=(int)checkNull($uid);
        $token=checkNull($token);
        $p=checkNull($p);
		
		$result=array(
			'data'=>array(),
			'nums'=>0,
			'isscroll'=>0,
		);
		
		if(checkToken($uid,$token)==700){
			echo json_encode($result);
			exit;
		} 
		
		$pnums=50;
		$start=($p-1)*$pnums;
		
		$list=Db::name('agent_profit_recode')->field("uid,sum(one_profit) as total")->where(["one_uid"=>$uid])->group("uid")->order("addtime desc")->limit($start,$pnums)->select()->toArray();
		foreach($list as $k=>$v){
			$list[$k]['userinfo']=getUserInfo($v['uid']);
			$list[$k]['total']=NumberFormat($v['total']);
		}
		
		$nums=count($list);
		if($nums<$pnums){
			$isscroll=0;
		}else{
			$isscroll=1;
		}
		
		$result=array(
			'data'=>$list,
			'nums'=>$nums,
			'isscroll'=>$isscroll,
		);

		echo json_encode($result);
		exit;
	}


    //用户商城提现
    private function setShopCash($data){

        $nowtime=time();

        $uid=$data['uid'];
        $accountid=$data['accountid'];
        $money=$data['money'];

        $configpri=getConfigPri();
        $balance_cash_start=$configpri['balance_cash_start'];
        $balance_cash_end=$configpri['balance_cash_end'];
        $balance_cash_max_times=$configpri['balance_cash_max_times'];

        $day=(int)date("d",$nowtime);

        if($day < $balance_cash_start || $day > $balance_cash_end){
            return 1005;
        }

        //本月第一天
        $month=date('Y-m-d',strtotime(date("Ym",$nowtime).'01'));
        $month_start=strtotime(date("Ym",$nowtime).'01');

        //本月最后一天
        $month_end=strtotime("{$month} +1 month");

        if($balance_cash_max_times){
            $count=Db::name('user_balance_cashrecord')
                ->where('uid','=',$uid)
                ->where('addtime','>',$month_start)
                ->where('addtime','<',$month_end)
                ->count();
            if($count >= $balance_cash_max_times){
                return 1006;
            }
        }


        /* 钱包信息 */
        $accountinfo=Db::name('cash_account')
            ->where('id','=',$accountid)
            ->where('uid','=',$uid)
            ->find();

        if(!$accountinfo){
            return 1007;
        }


        /* 最低额度 */
        $balance_cash_min=$configpri['balance_cash_min'];

        if($money < $balance_cash_min){
            return 1004;
        }


        $ifok=Db::name('user')
            ->where('id','=', $uid)
            ->where('balance','>=',$money)
//            ->update(array('balance' => new NotORM_Literal("balance - {$money}")) );
            ->setDec('balance',$money);

        if(!$ifok){
            return 1001;
        }



        $data=array(
            "uid"=>$uid,
            "money"=>$money,
            "orderno"=>$uid.'_'.$nowtime.rand(100,999),
            "status"=>0,
            "addtime"=>$nowtime,
            "type"=>$accountinfo['type'],
            "account_bank"=>$accountinfo['account_bank'],
            "account"=>$accountinfo['account'],
            "name"=>$accountinfo['name'],
        );

        $rs=Db::name('user_balance_cashrecord')->insert($data);
        if(!$rs){
            return 1002;
        }

        return $rs;
    }

    public function withdraw_list(){
        $rs=array('code'=>0,'info'=>array(),'msg'=>'成功');
        $data = $this->request->param();
        $uid=(int)checkNull($data['uid']);
        $token=checkNull($data['token']);

        $checkToken=checkToken($uid,$token);
        if($checkToken==700){
            $reason='您的登陆状态失效，请重新登陆！';
            $this->assign('reason', $reason);
            return $this->fetch(':error');
        }

        $list=Db::name('user_balance_cashrecord')
            ->where('uid','=',$uid)
            ->order('addtime desc')
            ->select();

        $rs['info'] = $list;
        echo json_encode($rs);
    }
}