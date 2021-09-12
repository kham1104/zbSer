<?php
/**
 * 会员认证
 */
namespace app\appapi\controller;

use cmf\controller\HomeBaseController;
use think\Db;
use cmf\lib\Upload;

class AuthController extends HomebaseController {
	
	public function index(){
		$data = $this->request->param();
        $uid=isset($data['uid']) ? $data['uid']: '';
        $token=isset($data['token']) ? $data['token']: '';
        $reset=isset($data['reset']) ? $data['reset']: '0';
        $uid=(int)checkNull($uid);
        $token=checkNull($token);
        $reset=checkNull($reset);
        
        $checkToken=checkToken($uid,$token);
		if($checkToken==700){
			$reason='您的登陆状态失效，请重新登陆！';
			$this->assign('reason', $reason);
			return $this->fetch(':error');
		}
        
        $user=[
            'id'=>$uid,
        ];
        session('user',$user);
        
		$this->assign("uid",$uid);
		$this->assign("token",$token);     

		if($reset!=1){				 
			$auth=Db::name("user_auth")->where(["uid"=>$uid])->find();
			if($auth){
				if($auth['status']==0){
                    return $this->fetch('success');
					exit;
				}else if($auth['status']==1){
					$this->assign("auth",$auth);
                    return $this->fetch('authstep2');
				}else if($auth['status']==2){
					$this->assign("reason",nl2br($auth['reason']));
                    return $this->fetch('error');
				}
			}

		}

		return $this->fetch();
	    
	}

	/* 图片上传 */
	public function upload(){
        
        // file_put_contents('./auth_upload.txt',date('Y-m-d H:i:s').' 提交参数信息 files:'.json_encode($_FILES)."\r\n",FILE_APPEND);
        $file=isset($_FILES['file'])?$_FILES['file']:'';
        if($file){
            $name=$file['name'];
            $pathinfo = pathinfo($name);
            if(!isset($pathinfo['extension'])){
                $_FILES['file']['name']=$name.'.jpg';
            }
        }
        $uploader = new Upload();
        $uploader->setFileType('image');
        $result = $uploader->upload();
        // file_put_contents('./auth_upload.txt',date('Y-m-d H:i:s').' 提交参数信息 result:'.json_encode($result)."\r\n",FILE_APPEND);
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
	/* 认证页面 */
	public function authstep(){
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
        
		$this->assign("uid",$uid);
		$this->assign("token",$token); 
		return $this->fetch();
	    
	}	
	/* 认证保存 */
	public function authsave(){
        
        $data = $this->request->param();
        $uid=isset($data['uid']) ? $data['uid']: '';
        $token=isset($data['token']) ? $data['token']: '';
        $uid=(int)checkNull($uid);
        $token=checkNull($token);
		
		if( !$uid || !$token || checkToken($uid,$token)==700 ){
            echo json_encode(array("ret"=>0,'data'=>array(),'msg'=>'您的登陆状态失效，请重新登陆！'));
			exit;
		} 
        
        $real_name=isset($data['real_name']) ? $data['real_name']: '';
        $mobile=isset($data['mobile']) ? $data['mobile']: '';
        $cer_no=isset($data['cer_no']) ? $data['cer_no']: '';
        $front_view=isset($data['front_view']) ? $data['front_view']: '';
        $back_view=isset($data['back_view']) ? $data['back_view']: '';
        $handset_view=isset($data['handset_view']) ? $data['handset_view']: '';
        
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
        
        
        $data2=[
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
        
		$result=Db::name("user_auth")->where(["uid"=>$data['uid']])->update($data2);
		if(!$result){
			$result=Db::name("user_auth")->insert($data2);
		}

		if($result!==false){
			echo json_encode(array("ret"=>200,'data'=>array(),'msg'=>''));
		}else{
			echo json_encode(array("ret"=>0,'data'=>array(),'msg'=>'提交失败，请重新提交'));
		}
        exit;
	}	
	/* 成功 */
	public function succ(){ 
        return $this->fetch('success');
	}
}