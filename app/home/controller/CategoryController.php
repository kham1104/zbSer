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
 * 分类页
 */
class CategoryController extends HomebaseController {
	
    //分类页
	public function index() {

        
		/* 主播列表 */
        $data = $this->request->param();
        
        $cat=isset($data['cat']) ? $data['cat']: '';
			switch($cat){
				case "1":
						$where="u.sex='1' and l.islive='1' ";
						$site_seo_title='国民男神';
						$current=1;	
						break;
				case "2":
						$where="u.sex='2' and l.islive='1' ";
						$site_seo_title='女神驾到';
						$current=2;
						break;
				default :
						$where="l.islive='1'";
						$site_seo_title='';
                        $current='0';
						break;			
				
				
			}
			
			$this->assign("current",$current);	
			
	    
			$count = Db::name("live l")
					->leftjoin("user u","u.id=l.uid")
					->where($where)
					->count();// 查询满足要求的总记录数
			

			// 进行分页数据查询 注意limit方法的参数要使用Page类的属性
			$lists=Db::name("live l")
					->field("u.user_nicename,u.avatar,l.thumb,l.uid,l.stream,l.title,l.city,l.islive")
					->leftjoin("user u","u.id=l.uid")
					->where($where)
					->order("l.showid desc")
					->paginate(20);
			$lists->each(function($v,$k){
                $v['avatar']=get_upload_path($v['avatar']);
                $v['thumb']=get_upload_path($v['thumb']);
                if($v['thumb']==''){
                    $v['thumb']=$v['avatar'];
                }
                return $v;
            }); 
            $lists->appends($data);
            $page = $lists->render();
            
			$this->assign('lists',$lists);// 赋值数据集
			$this->assign('page',$page);// 赋值分页输出		
			$this->assign('site_seo_title',$site_seo_title);	
            return $this->fetch();
    }	


}


