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

class PaymentController extends HomebaseController {
	
    
	public function index() {	
		LogIn();
		$uid=(int)session("uid");
        if(!$uid){
            $this->error('请先登录');
        }
    	$lists = Db::name("charge_rules")
				->order("list_order asc")
				->select();
                
    	$this->assign('lists', $lists);
        
		$user=Db::name('user')->where("id={$uid}")->find();
        
		$this->assign("user",$user);
    	return $this->fetch();
    }
	//调用微信扫码支付接口=以及支付宝扫码支付===
  	public function chargepay(){

        $uid=session("uid");
        if($uid<1){
            $this->error("您的登陆状态失效，请重新登陆！");
        }
        
        $changeid = $this->request->param('changeid', 0, 'intval');
		
        if(!$changeid){
            $this->error("参数错误");
        }
		$where['id']=$changeid;
		$charge=Db::name("charge_rules")->where($where)->find();
		
		if(!$charge ){
			$this->error("订单信息有误，请重新提交");
		}
		
		
		$money = $charge['money'];
		$coin = $charge['coin'];
		$give = $charge['give'];
		//读取后台配置信息
		$getConfigPri=getConfigPri();	
		$getConfigPub=getConfigPub();
		//当前域名
		$pay_url=$getConfigPub['site']; 
		//商户订单号 //便于筛选，订单号为 uid_touid_时间戳_随机数
		$orderid = $uid."_".$uid."_".date("mdHis")."_".rand(999,9999);
        $paytype=$_POST['c_PPPayID'];

		if($paytype == 'weixin'){

			require_once CMF_ROOT."sdk/wxpay/lib/WxPay.Api.php"; 
			require_once CMF_ROOT."sdk/wxpay/pay/WxPay.NativePay.php";
			//debug_backtrace
			$notify = new \NativePay();
			//订单名称
			$order_name = "充值".$getConfigPub['name_coin'].",价值为".$coin;
			///支付记录
            //touid 赠送人id money充值金额 coin兑换点数 orderno商户订单号 trade_no第三方订单号 status订单支付状态 addtime订单提交时间 type支付方式(1 支付宝2微信 3苹果支付)
			$data=array(
				'touid' =>$uid,
				'uid'=>$uid,
				'money' => $money,
				'coin' =>$coin,
				'coin_give' =>$give,
				'trade_no'=>'',
				'orderno'=>$orderid,
				'status'=>0,		
				'addtime'=>time(),
				'type'=>2,
				'ambient'=>2,
			);	
			$userid=Db::name("charge_user")->insert($data);
			//支付记录
			$money2 = $money*100;
			$input = new \WxPayUnifiedOrder();
			$input->SetBody($order_name);
			$input->SetAttach($order_name);
			$input->SetOut_trade_no($orderid);
			$input->SetTotal_fee($money2);
			$input->SetTime_start(date("YmdHis"));
			$input->SetTime_expire(date("YmdHis", time() + 600));
			$input->SetGoods_tag("test");
			$input->SetNotify_url($pay_url."/appapi/wxpay/notify_native");
			$input->SetTrade_type("NATIVE");
			$input->SetProduct_id("123456789");
			$result = $notify->GetPayUrl($input);
            
			$url2 = $result["code_url"];        			
			echo '<html>
				    <head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8"></head>
				    <body>
				      <form name="form1" id="form1" method="post" action="/home/Payment/wxpay" target="_self">
								<input type="hidden" name="url" value="'. $url2.'" />
								<input type="hidden" name="money" value="'. $money.'" />
								<input type="hidden" name="coin" value="'. $coin.'" />
								<input type="hidden" name="orderid" value="'. $orderid.'" />
								<script language="javascript">document.form1.submit();</script>
							</form></body></html>
						';	
				exit();	
        }
        
		//支付宝扫码支付===========================
        if($paytype == 'zhifubao'){

			//获取后台设置的 配置信息
			/*  $siteconfig=M("siteconfig")->where("id='1'")->find(); */
			//↓↓↓↓↓↓↓↓↓↓请在这里配置您的基本信息↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓
			//合作身份者id，以2088开头的16位纯数字
			$alipay_config['partner']=$getConfigPri['aliapp_partner'];
			//安全检验码，以数字和字母组成的32位字符
			$alipay_config['key']			= $getConfigPri['aliapp_check'];
			//支付宝账号
			$alipay_config['seller_email'] =$getConfigPri['aliapp_seller_id'];
			//↑↑↑↑↑↑↑↑↑↑请在这里配置您的基本信息↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑
			//签名方式 不需修改
			$alipay_config['sign_type']    = strtoupper('MD5');
			//字符编码格式 目前支持 gbk 或 utf-8
			$alipay_config['input_charset']= strtolower('utf-8');
			//ca证书路径地址，用于curl中ssl校验
			//请保证cacert.pem文件在当前文件夹目录中
			$alipay_config['cacert']    = CMF_ROOT.'sdk/alipay/cacert.pem';
			//访问模式,根据自己的服务器是否支持ssl访问，若支持请选择https；若不支持请选择http
			$alipay_config['transport']    = 'http';
			//↓↓↓↓↓↓↓↓↓↓请在这里配置您的基本信息↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓
			require_once CMF_ROOT."sdk/alipay/lib/alipay_submit.class.php";
			//支付记录
			//touid 赠送人id money充值金额 coin兑换点数 orderno商户订单号 trade_no第三方订单号 status订单支付状态 addtime订单提交时间 type支付方式(1 支付宝2微信 3苹果支付)
			$data=array(
					'touid' =>$uid,
					'uid'=>$uid,
					'money' => $money,
					'coin' =>$coin,
					'coin_give' =>$give,
					'trade_no'=>'',
					'orderno'=>$orderid,
					'status'=>0,
					'addtime'=>time(),
					'type'=>1,
					'ambient'=>1,
				);	
			$userid=Db::name("charge_user")->insert($data);
            //支付记录					
			/**************************请求参数**************************/
			//支付类型
			$payment_type = "1";
			//必填，不能修改
			//服务器异步通知页面路径
			$notify_url = $pay_url."/home/Payment/alipay_d_notify";
			//需http://格式的完整路径，不能加?id=123这类自定义参数
			//页面跳转同步通知页面路径
			$return_url = $pay_url."/home/Payment/index";
			//需http://格式的完整路径，不能加?id=123这类自定义参数，不能写成http://localhost/
			//商户网站订单系统中唯一订单号，必填
			//订单名称
			$subject ="支付宝充值".$getConfigPub['name_coin'];
			//付款金额
			$total_fee =$money;
			//订单描述
			$body = "充值".$getConfigPub['name_coin'].",价格为".$coin;
			//商品展示地址
			$show_url =$pay_url;
			//需以http://开头的完整路径，例如：http://www.xxx.com/myorder.html
			//防钓鱼时间戳
			$anti_phishing_key = "";
			//若要使用请调用类文件submit中的query_timestamp函数
			//客户端的IP地址
			$exter_invoke_ip = "";
			//非局域网的外网IP地址，如：221.0.0.1
			/************************************************************/
			//构造要请求的参数数组，无需改动
				$parameter = array(
						"service" => "create_direct_pay_by_user",
						"partner" => trim($alipay_config['partner']),
						"payment_type"	=> $payment_type,
						"notify_url"	=> $notify_url,
						"return_url"	=> $return_url,
						"seller_email"	=> trim($alipay_config['seller_email']),
						"out_trade_no"	=> $orderid,
						"subject"	=> $subject,
						"total_fee"	=> $total_fee,
						"body"	=> $body,
						"show_url"	=> $show_url,
						"qr_pay_mode"=>2,
						"anti_phishing_key"	=> $anti_phishing_key,
						"exter_invoke_ip"	=> $exter_invoke_ip,
						"_input_charset"	=> trim(strtolower($alipay_config['input_charset']))
				);
				
			//建立请求
			$alipaySubmit = new \AlipaySubmit($alipay_config);
			$html_text = $alipaySubmit->buildRequestForm($parameter,"get", "1"); 
			/*  $html_text = $alipaySubmit->buildRequestPara($parameter); */      
			echo $html_text;
            exit;
		}


	}	
	
	//==========================
	
	
	//支付宝即时到帐  返回处理
	public function alipay_d_notify(){
        
		//读取后台配置信息
		$getConfigPri=getConfigPri();	
		$getConfigPub=getConfigPub();
		//↓↓↓↓↓↓↓↓↓↓请在这里配置您的基本信息↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓
		//合作身份者id，以2088开头的16位纯数字
		$alipay_config['partner']=$getConfigPri['aliapp_partner'];
		//安全检验码，以数字和字母组成的32位字符
		$alipay_config['key']			= $getConfigPri['aliapp_check'];
		//支付宝账号
		$alipay_config['seller_email'] =$getConfigPri['aliapp_seller_id'];
		//↑↑↑↑↑↑↑↑↑↑请在这里配置您的基本信息↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑
		//签名方式 不需修改
		$alipay_config['sign_type']    = strtoupper('MD5');
		//字符编码格式 目前支持 gbk 或 utf-8
		$alipay_config['input_charset']= strtolower('utf-8');
		//ca证书路径地址，用于curl中ssl校验
		//请保证cacert.pem文件在当前文件夹目录中
		$alipay_config['cacert']    = CMF_ROOT.'sdk/alipay/cacert.pem';
		//访问模式,根据自己的服务器是否支持ssl访问，若支持请选择https；若不支持请选择http
		$alipay_config['transport']    = 'http';
		//↓↓↓↓↓↓↓↓↓↓请在这里配置您的基本信息↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓	
		require_once(CMF_ROOT."sdk/alipay/lib/alipay_notify.class.php");
		//计算得出通知验证结果
		$alipayNotify = new \AlipayNotify($alipay_config);
		$verify_result = $alipayNotify->verifyNotify();
		if($verify_result) {//验证成功
			/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
			//请在这里加上商户的业务逻辑程序代
			//——请根据您的业务逻辑来编写程序（以下代码仅作参考）——
			//获取支付宝的通知返回参数，可参考技术文档中服务器异步通知参数列表
			//session("uid")."_".session("uid")."_".date("mdHis")."_".rand(999,9999); 
			//商户订单号
			$out_trade_no = $_POST['out_trade_no'];
			//支付宝交易号
			$trade_no = $_POST['trade_no'];
			//交易状态
			$trade_status = $_POST['trade_status'];
			//交易金额
			$total_fee = $_POST['total_fee'];

			if($trade_status == 'TRADE_FINISHED') {
				//判断该笔订单是否在商户网站中已经做过处理
				//如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
				//如果有做过处理，不执行商户的业务程序
					
				//注意：
				//退款日期超过可退款期限后（如三个月可退款），支付宝系统发送该交易状态通知
				//请务必判断请求时的total_fee、seller_id与通知时获取的total_fee、seller_id为一致的

				//调试用，写文本函数记录程序运行情况是否正常
				//logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");
		
			}else if ($trade_status == 'TRADE_SUCCESS') {
				//判断该笔订单是否在商户网站中已经做过处理
				//如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
				//如果有做过处理，不执行商户的业务程序
					
				//注意：
				//付款完成后，支付宝系统发送该交易状态通知
				//请务必判断请求时的total_fee、seller_id与通知时获取的total_fee、seller_id为一致的

				//调试用，写文本函数记录程序运行情况是否正常
				//logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");
                
                $where['orderno']=$out_trade_no;
                $where['money']=$total_fee;
                $where['type']=1;
                
                $data=[
                    'trade_no'=>$trade_no
                ];

				$this->logali("where:".json_encode($where));	
                $res=handelCharge($where,$data);
				if($res==0){
                    $this->logali("orderno:".$out_trade_no.' 订单信息不存在');	
                    echo "fail";
                    exit;
				}
                
                $this->logali("成功");
                echo "success";		//请不要修改或删除
                exit;											
			}
			
		
			//更新会员余额
			//——请根据您的业务逻辑来编写程序（以上代码仅作参考）——
			echo "fail";		//请不要修改或删除
            exit;
			/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////	/* 打印log */
		//file_put_contents('./logali.txt',date('y-m-d h:i:s').'  msg:'.$msg."\r\n",FILE_APPEND);
		}	
		else {
			//验证失败
			echo "fail";
            exit;
			//调试用，写文本函数记录程序运行情况是否正常
			//logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");
		}	
	}	
	//支付宝即时到帐  返回处理	
	
	
	/* 打印log */
	public function logali($msg){
		//file_put_contents('./logali.txt',date('y-m-d h:i:s').'  msg:'.$msg."\r\n",FILE_APPEND);
	}	
	//===========================
	
	
	public function getOrderStatus(){
		require_once CMF_ROOT."sdk/wxpay/lib/WxPay.Api.php";
		require_once CMF_ROOT."sdk/wxpay/lib/WxPay.Notify.php";
		require_once CMF_ROOT."sdk/wxpay/pay/notify.php";
	   
	 	$orderid = $_GET['orderid'];
	 	$notify = new \PayNotifyCallBack();
		$wxpayStatus=$notify->Queryorder($orderid);
		
		$order_info = explode("_",$orderid); 
		$uid = $order_info[0];
		$touid = $order_info[1];
		
		//获取该订单在数据库内的信息
        
        $where['orderno']=$orderid;
        
		$orderinfo=Db::name("charge_user")->where($where)->find();

		if($orderinfo['status']==1){
            echo 1;
            exit;
		}
       
		//订单是否真正支付
		if($wxpayStatus['trade_state']=='SUCCESS'){
			
			if($wxpayStatus['out_trade_no']==$orderid && $orderinfo['status']==0){
				echo 1;
                exit;
			}

		}elseif($wxpayStatus['trade_state']=='NOTPAY'){
			echo 0 ; //未支付
            exit;
		}else{
			echo 0;//未知错误
            exit;
		} 
	}
	public function wxpay(){
        
        $data = $this->request->param();
        $url=isset($data['url']) ? $data['url']: '';
        $money=isset($data['money']) ? $data['money']: '0';
        $coin=isset($data['coin']) ? $data['coin']: '0';
        $orderid=isset($data['orderid']) ? $data['orderid']: '';

        
        $logo="微信扫码支付";
		$this->assign("logo",$logo);
		$this->assign("url",$url);
		
        $this->assign("money",$money);
        $this->assign("coin",$coin);
        $this->assign("orderid",$orderid);
    
        return $this->fetch();
	}
  
    
    public function mylist() {
		
    	return $this->fetch();
    }
    
    public function qrcode(){
        error_reporting(E_ERROR);
        require_once CMF_ROOT.'sdk/wxpay/pay/phpqrcode/phpqrcode.php';
        $url = urldecode($_GET["data"]);
        \QRcode::png($url);
        exit;
    }

}


