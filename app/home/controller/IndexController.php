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
/**
 * 首页
 */
class IndexController extends HomebaseController {
	
    //首页
	public function index() {

		$this->assign("current",'index');
        
		/*获取推荐播放列表(正在直播，推荐，按粉丝数排序)*/
		$indexLive=Db::name("live")->where("islive='1' and isrecommend='1' and type='0'")->select()->toArray();

		foreach ($indexLive as $k => $v){
            $userinfo=getUserInfo($v['uid']);
            
            $v['avatar']=$userinfo['avatar'];
			$v['avatar_thumb']=$userinfo['avatar_thumb'];
			$v['user_nicename']=$userinfo['user_nicename'];
            
			if($v['thumb']==""){
				$v['thumb']=$v['avatar'];
			}
			if($v['isvideo']==0){
                if($this->configpri['cdn_switch']!=5){
                    $v['pull']=PrivateKeyA('http',$v['stream'].'.flv',0);
                }
            }
			$v['fans_nums']=Db::name("user_attention")->where("touid={$v['uid']}")->count();
            $indexLive[$k]=$v;
		}
        $indexLive1=[];
        if($indexLive){
            $sort=array_column($indexLive,"fans_nums");
            array_multisort($sort, SORT_DESC, $indexLive);
            $indexLive1=array_slice($indexLive,0,4);
        }
		
		$this->assign("indexLive",$indexLive1);
		//var_dump($indexLive1);
		/* 轮播 */
		$slide=Db::name("slide_item")->where("status='1' and slide_id='1'")->order("list_order asc")->select()->toArray();
        foreach($slide as $k=>$v){
            $v['image']=get_upload_path($v['image']);
            $slide[$k]=$v;
        }
		$this->assign("slide",$slide);	

		/* 推荐（正在直播 在线人数） */
		$recommend=Db::name("live ")
					->field("uid,thumb,uid,stream,type,islive")
					->where("islive='1'")
					->limit(12)
					->select()
                    ->toArray();
		foreach($recommend as $k=>$v){
            $userinfo=getUserInfo($v['uid']);
            
            $v['avatar']=$userinfo['avatar'];
			$v['avatar_thumb']=$userinfo['avatar_thumb'];
			$v['user_nicename']=$userinfo['user_nicename'];
	 		if($v['thumb']=="")
			{
				$v['thumb']=$v['avatar'];
			} 
			$nums=zSize('user_'.$v['stream']);
			$v['nums']=$nums;
            $recommend[$k]=$v;
		}
        if($recommend){
            $sort=array_column($recommend,"nums");
            $sort1=array_column($recommend,"uid");
            array_multisort($sort, SORT_DESC,$sort1,SORT_DESC, $recommend);
        }
        
		$this->assign("recommend",$recommend);			 
			 
		/* 热门（在直播，推荐为热门） */
		$hot=Db::name("live")
					->field("uid,thumb,stream,title,city,islive,type")
					->where("islive='1' and ishot='1'")
					->order("isrecommend desc,starttime desc")
					->limit(10)
					->select()
                    ->toArray();
        foreach($hot as $k=>$v){
            $userinfo=getUserInfo($v['uid']);

            $v['avatar']=$userinfo['avatar'];
            $v['avatar_thumb']=$userinfo['avatar_thumb'];
            $v['user_nicename']=$userinfo['user_nicename'];
            $v['signature']=$userinfo['signature'];
            $nums=zSize('user_'.$v['stream']);

            $v['nums']=(string)$nums;
            if($v['thumb']=="")
            {
                $v['thumb']=$v['avatar'];
            }
            $hot[$k]=$v;
		} 
		$this->assign("hot",$hot);

		/* 最新直播（在直播，按开播时间倒序） */ 
		$live=Db::name("live")->field("uid,thumb,stream,title,city,islive,type")->where("islive='1'")->order("starttime desc")->limit(10)->select()->toArray();
		foreach($live as $k=>$v){
            $userinfo=getUserInfo($v['uid']);
            
            $v['avatar']=$userinfo['avatar'];
			$v['avatar_thumb']=$userinfo['avatar_thumb'];
			$v['user_nicename']=$userinfo['user_nicename'];
			$v['signature']=$userinfo['signature'];
            
			$nums=zSize('user_'.$v['stream']);
			$v['nums']=(string)$nums;

			if($v['thumb']==""){
				$v['thumb']=$v['avatar'];
			}
            $live[$k]=$v;
		} 

		$this->assign("live",$live);

    	return $this->fetch();
    }	
	
	public function translate()
	{
        
        $this->assign("current",'');
        
        $keyword = $this->request->param('keyword');
        
		$keyword=checkNull($keyword);
        
		$pagesize = 18; 
		if($keyword=="")
		{
            $data = $this->request->param();
			$lists=Db::name("live")
					->field('uid,stream,title,city,islive')
					->where('islive=1')
					->order("starttime desc")
					->paginate($pagesize);
            $lists->each(function($v,$k){
                $userinfo=getUserInfo($v['uid']);
                
                $v['user_nicename']=$userinfo['user_nicename'];
                $v['avatar']=$userinfo['avatar'];
                return $v;
            });
            
            $lists->appends($data);
            $page = $lists->render();
            
			$msg["info"]='抱歉,没有找到关于"';
			$msg["name"]='';
			$msg["result"]='"的搜索结果';
			$msg["type"]='0';
		}else{
            $data = $this->request->param();
            $where1=[
                ['user_type','=',2],
                ['id','=',$keyword]
            ];
            $where2=[
                ['user_type','=',2],
                ['user_nicename','like','%'.$keyword.'%'],
            ];
            
			$count= Db::name('user')->whereor([$where1,$where2])->count();
            
            $lists = Db::name("user")
                ->whereor([$where1,$where2])
                ->order("consumption DESC")
                ->paginate($pagesize);
                
            $lists->each(function($v,$k){
                $v['avatar']=get_upload_path($v['avatar']);
                $v['islive']='0';
                $v['title']='';
                return $v;
            });
                
            $lists->appends($data);
            $page = $lists->render();

            
			$msg["info"]='共找到'.$count.'个关于"';
			$msg["name"]=$keyword;
			$msg["result"]='"的搜索结果';
			$msg["type"]='1';
		}
        
		$this->assign('lists',$lists);
		$this->assign('msg',$msg);
		$this->assign('page',$page);
		$this->assign('keyword',$keyword);
        
		return $this->fetch();
	}	
    
    /* 图片裁剪 */
    function cutImg(){
        
        $data = $this->request->param();
        $filepath=isset($data['filepath']) ? $data['filepath']: '';
        $filepath=checkNull($filepath);
        
        $width=isset($data['width']) ? $data['width']: '';
        $new_width=checkNull($width);
        
        $height=isset($data['height']) ? $data['height']: '';
        $new_height=checkNull($height);
        
        $source_info   = getimagesize($filepath);
        $source_width  = $source_info[0];
        $source_height = $source_info[1];
        $source_mime   = $source_info['mime'];
        $source_ratio  = $source_height / $source_width;
        $target_ratio  = $new_height / $new_width;
        // 源图过高
        if ($source_ratio > $target_ratio){

            $cropped_width  = $source_width;
            $cropped_height = $source_width * $target_ratio;
            $source_x = 0;
            $source_y = ($source_height - $cropped_height) / 2;
        }
        // 源图过宽
        elseif ($source_ratio < $target_ratio){
        	
            $cropped_width  = $source_height / $target_ratio;
            $cropped_height = $source_height;
            $source_x = ($source_width - $cropped_width) / 2;
            $source_y = 0;
        }
        // 源图适中
        else{

            $cropped_width  = $source_width;
            $cropped_height = $source_height;
            $source_x = 0;
            $source_y = 0;
        }

        switch ($source_mime){
            case 'image/gif':
                $source_image = imagecreatefromgif($filepath);
                break;
            case 'image/jpeg':
                $source_image = imagecreatefromjpeg($filepath);
                break;
            case 'image/png':
                $source_image = imagecreatefrompng($filepath);
                break;
            default:
                return false;
            break;
        }

        $target_image  = imagecreatetruecolor($new_width, $new_height);
        $cropped_image = imagecreatetruecolor($cropped_width, $cropped_height);
        // 裁剪
        imagecopy($cropped_image, $source_image, 0, 0, $source_x, $source_y, $cropped_width, $cropped_height);
        // 缩放
        imagecopyresampled($target_image, $cropped_image, 0, 0, 0, 0, $new_width, $new_height, $cropped_width, $cropped_height);
        header('Content-Type: image/jpeg');
        imagejpeg($target_image);
        imagedestroy($source_image);
        imagedestroy($target_image);
        imagedestroy($cropped_image);
    }

}


