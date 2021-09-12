<?php
if (!session_id()) session_start();
class Model_Home extends PhalApi_Model_NotORM {
    protected $live_fields='uid,title,city,stream,pull,thumb,isvideo,type,type_val,goodnum,anyway,starttime,isshop,game_action';
     
    
	/* 轮播 */
	public function getSlide(){

		$rs=DI()->notorm->slide_item
			->select("image as slide_pic,url as slide_url")
			->where("status='1' and slide_id='2' ")
			->order("list_order asc")
			->fetchAll();
		foreach($rs as $k=>$v){
			$rs[$k]['slide_pic']=get_upload_path($v['slide_pic']);
		}				

		return $rs;
	}

	/* 热门 */
    public function getHot($p) {
        if($p<1){
            $p=1;
        }
		$pnum=50;
		$start=($p-1)*$pnum;
		$where=" islive= '1' and ishot='1' ";
        
        if($p==1){
			$_SESSION['hot_starttime']=time();
		}
        
		if($p!=0){
			$endtime=$_SESSION['hot_starttime'];
            if($endtime){
                $where.=" and starttime < {$endtime}";
            }
			
		}
        
        if($p!=1){
			$hotvotes=$_SESSION['hot_hotvotes'];
            if($hotvotes){
                $where.=" and hotvotes < {$hotvotes}";
            }else{
                $where.=" and hotvotes < 0";
            }
			
		}
		
		$result=DI()->notorm->live
                    ->select($this->live_fields.',hotvotes')
                    ->where($where)
                    ->order('isrecommend desc,hotvotes desc,starttime desc')
                    ->limit(0,$pnum)
                    ->fetchAll();
                    
		foreach($result as $k=>$v){
            
			$v=handleLive($v);
            
            $result[$k]=$v;
			
		}	
		if($result){
			$last=end($result);
			//$_SESSION['hot_starttime']=$last['starttime'];
			$_SESSION['hot_hotvotes']=$last['hotvotes'];
		}

		return $result;
    }
	
		/* 关注列表 */
    public function getFollow($uid,$p) {
        $rs=array(
            'title'=>'你还没有关注任何主播',
            'des'=>'赶快去关注自己喜欢的主播吧~',
            'list'=>array(),
        );
        if($p<1){
            $p=1;
        }
		$result=array();
		$pnum=50;
		$start=($p-1)*$pnum;
		
		$touid=DI()->notorm->user_attention
				->select("touid")
				->where('uid=?',$uid)
				->fetchAll();
				
		if(!$touid){
            return $rs;
        }
        
        $rs['title']='你关注的主播没有开播';
        $rs['des']='赶快去看看其他主播的直播吧~';
        $where=" islive='1' ";					
        if($p!=1){
            $endtime=$_SESSION['follow_starttime'];
            if($endtime){
                $start=0;
                $where.=" and starttime < {$endtime}";
            }
            
        }	
    
        $touids=array_column($touid,"touid");
        $touidss=implode(",",$touids);
        $where.=" and uid in ({$touidss})";
        $result=DI()->notorm->live
                ->select($this->live_fields)
                ->where($where)
                ->order("starttime desc")
                ->limit(0,$pnum)
                ->fetchAll();
	
		foreach($result as $k=>$v){
            
			$v=handleLive($v);
            
            $result[$k]=$v;
		}	

		if($result){
			$last=end($result);
			$_SESSION['follow_starttime']=$last['starttime'];
		}
        
        $rs['list']=$result;

		return $rs;					
    }
		
		/* 最新 */
    public function getNew($lng,$lat,$p) {
        if($p<1){
            $p=1;
        }
		$pnum=50;
		$start=($p-1)*$pnum;
		$where=" islive='1' ";

		if($p!=1){
			$endtime=$_SESSION['new_starttime'];
            if($endtime){
                $where.=" and starttime < {$endtime}";
            }
		}
		
		$result=DI()->notorm->live
				->select($this->live_fields.',lng,lat')
				->where($where)
				->order("starttime desc")
				->limit(0,$pnum)
				->fetchAll();	
		foreach($result as $k=>$v){
            
			$v=handleLive($v);
			
			$distance='好像在火星';
			if($lng!='' && $lat!='' && $v['lat']!='' && $v['lng']!=''){
				$distance=getDistance($lat,$lng,$v['lat'],$v['lng']);
			}else if($v['city']){
				$distance=$v['city'];	
			}
			
			$v['distance']=$distance;
			unset($v['lng']);
			unset($v['lat']);
            
            $result[$k]=$v;
			
		}		
		if($result){
			$last=end($result);
			$_SESSION['new_starttime']=$last['starttime'];
		}

		return $result;
    }
		
		/* 搜索 */
    public function search($uid,$key,$p) {
        if($p<1){
            $p=1;
        }
		$pnum=50;
		$start=($p-1)*$pnum;
		$where=' user_type="2" and ( id=? or user_nicename like ?  or goodnum like ? ) and id!=?';
		if($p!=1){
			$id=$_SESSION['search'];
            if($id){
                $where.=" and id < {$id}";
            }
		}
		
		$result=DI()->notorm->user
				->select("id,user_nicename,avatar,sex,signature,consumption,votestotal")
				->where($where,$key,'%'.$key.'%','%'.$key.'%',$uid)
				->order("id desc")
				->limit($start,$pnum)
				->fetchAll();
		foreach($result as $k=>$v){
			$v['level']=(string)getLevel($v['consumption']);
			$v['level_anchor']=(string)getLevelAnchor($v['votestotal']);
			$v['isattention']=(string)isAttention($uid,$v['id']);
			$v['avatar']=get_upload_path($v['avatar']);
			unset($v['consumption']);
            
            $result[$k]=$v;
		}				
		
		if($result){
			$last=end($result);
			$_SESSION['search']=$last['id'];
		}
		
		return $result;
    }
	
	/* 附近 */
    public function getNearby($lng,$lat,$p) {
        if($p<1){
            $p=1;
        }
		$pnum=50;
		$start=($p-1)*$pnum;
		$where=" islive='1' and lng!='' and lat!='' ";
		
		$result=DI()->notorm->live
				->select($this->live_fields.",getDistance('{$lat}','{$lng}',lat,lng) as distance,province")
				->where($where)
                ->order("distance asc")
                ->limit($start,$pnum)
				->fetchAll();	
		foreach($result as $k=>$v){
            
			$v=handleLive($v);
            
            if($v['distance']>1000){
                $v['distance']=1000;
            }
            $v['distance']=$v['distance'].'km';

            $result[$k]=$v;
		}
		
		return $result;
    }


	/* 推荐 */
	public function getRecommend(){

		$result=DI()->notorm->user
				->select("id,user_nicename,avatar,avatar_thumb")
				->where("isrecommend='1'")
				->order("votestotal desc")
				->limit(0,12)
				->fetchAll();
		foreach($result as $k=>$v){
			$v['avatar']=get_upload_path($v['avatar']);
			$v['avatar_thumb']=get_upload_path($v['avatar_thumb']);
			$fans=getFans($v['id']);
			$v['fans']='粉丝 · '.$fans;
            
            $result[$k]=$v;
		}
		return  $result;
	}
	/* 关注推荐 */
	public function attentRecommend($uid,$touids){
		//$users=$this->getRecommend();
		//$users=explode(',',$touids);
        //file_put_contents('./attentRecommend.txt',date('Y-m-d H:i:s').' 提交参数信息 touids:'.$touids."\r\n",FILE_APPEND);
        $users=preg_split('/,|，/',$touids);
		foreach($users as $k=>$v){
			$touid=$v;
            //file_put_contents('./attentRecommend.txt',date('Y-m-d H:i:s').' 提交参数信息 touid:'.$touid."\r\n",FILE_APPEND);
			if($touid && !isAttention($uid,$touid)){
				DI()->notorm->user_black
					->where('uid=? and touid=?',$uid,$touid)
					->delete();
				DI()->notorm->user_attention
					->insert(array("uid"=>$uid,"touid"=>$touid));
			}
			
		}
		return 1;
	}

	/*获取收益排行榜*/
	public function profitList($uid,$type,$p){
        if($p<1){
            $p=1;
        }
		$pnum=50;
		$start=($p-1)*$pnum;

		switch ($type) {
			case 'day':
				//获取今天开始结束时间
				$dayStart=strtotime(date("Y-m-d"));
				$dayEnd=strtotime(date("Y-m-d 23:59:59"));
				$where=" addtime >={$dayStart} and addtime<={$dayEnd} and ";

			break;

			case 'week':
                $w=date('w'); 
                //获取本周开始日期，如果$w是0，则表示周日，减去 6 天 
                $first=1;
                //周一
                $week=date('Y-m-d H:i:s',strtotime( date("Ymd")."-".($w ? $w - $first : 6).' days')); 
                $week_start=strtotime( date("Ymd")."-".($w ? $w - $first : 6).' days'); 

                //本周结束日期 
                //周天
                $week_end=strtotime("{$week} +1 week")-1;
                
				$where=" addtime >={$week_start} and addtime<={$week_end} and ";

			break;

			case 'month':
                //本月第一天
                $month=date('Y-m-d',strtotime(date("Ym").'01'));
                $month_start=strtotime(date("Ym").'01');

                //本月最后一天
                $month_end=strtotime("{$month} +1 month")-1;

				$where=" addtime >={$month_start} and addtime<={$month_end} and ";

			break;

			case 'total':
				$where=" ";
			break;
			
			default:
				//获取今天开始结束时间
				$dayStart=strtotime(date("Y-m-d"));
				$dayEnd=strtotime(date("Y-m-d 23:59:59"));
				$where=" addtime >={$dayStart} and addtime<={$dayEnd} and ";
			break;
		}




		$where.=" action in (1,2)";
		
		$result=DI()->notorm->user_voterecord
            ->select('sum(total) as totalcoin, uid')
            ->where($where)
            ->group('uid')
            ->order('totalcoin desc')
            ->limit($start,$pnum)
            ->fetchAll();

		foreach ($result as $k => $v) {
            $userinfo=getUserInfo($v['uid']);
            $v['avatar']=$userinfo['avatar'];
			$v['avatar_thumb']=$userinfo['avatar_thumb'];
			$v['user_nicename']=$userinfo['user_nicename'];
			$v['sex']=$userinfo['sex'];
			$v['level']=$userinfo['level'];
			$v['level_anchor']=$userinfo['level_anchor'];
			$v['totalcoin']=(string)intval($v['totalcoin']);
            
            $v['isAttention']=isAttention($uid,$v['uid']);//判断当前用户是否关注了该主播
            
            $result[$k]=$v;
		}


		return $result;
	}



	/*获取消费排行榜*/
	public function consumeList($uid,$type,$p){
        if($p<1){
            $p=1;
        }
		$pnum=50;
		$start=($p-1)*$pnum;

		switch ($type) {
			case 'day':
				//获取今天开始结束时间
				$dayStart=strtotime(date("Y-m-d"));
				$dayEnd=strtotime(date("Y-m-d 23:59:59"));
				$where=" addtime >={$dayStart} and addtime<={$dayEnd} and ";

			break;
            
            case 'week':
                $w=date('w'); 
                //获取本周开始日期，如果$w是0，则表示周日，减去 6 天 
                $first=1;
                //周一
                $week=date('Y-m-d H:i:s',strtotime( date("Ymd")."-".($w ? $w - $first : 6).' days')); 
                $week_start=strtotime( date("Ymd")."-".($w ? $w - $first : 6).' days'); 

                //本周结束日期 
                //周天
                $week_end=strtotime("{$week} +1 week")-1;
                
				$where=" addtime >={$week_start} and addtime<={$week_end} and ";

			break;

			case 'month':
                //本月第一天
                $month=date('Y-m-d',strtotime(date("Ym").'01'));
                $month_start=strtotime(date("Ym").'01');

                //本月最后一天
                $month_end=strtotime("{$month} +1 month")-1;

				$where=" addtime >={$month_start} and addtime<={$month_end} and ";

			break;

			case 'total':
				$where=" ";
			break;
			
			default:
				//获取今天开始结束时间
				$dayStart=strtotime(date("Y-m-d"));
				$dayEnd=strtotime(date("Y-m-d 23:59:59"));
				$where=" addtime >={$dayStart} and addtime<={$dayEnd} and ";
			break;
		}

		$where.=" type=0 and action in ('1','2')";
		
        $result=DI()->notorm->user_coinrecord
            ->select('sum(totalcoin) as totalcoin, uid')
            ->where($where)
            ->group('uid')
            ->order('totalcoin desc')
            ->limit($start,$pnum)
            ->fetchAll();

		foreach ($result as $k => $v) {
            $userinfo=getUserInfo($v['uid']);
            $v['avatar']=$userinfo['avatar'];
			$v['avatar_thumb']=$userinfo['avatar_thumb'];
			$v['user_nicename']=$userinfo['user_nicename'];
			$v['sex']=$userinfo['sex'];
			$v['level']=$userinfo['level'];
			$v['level_anchor']=$userinfo['level_anchor'];
            
            $v['isAttention']=isAttention($uid,$v['uid']);//判断当前用户是否关注了该主播
            
            $result[$k]=$v;
		}

		return $result;
	}
    
    /* 分类下直播 */
    public function getClassLive($liveclassid,$p) {
        if($p<1){
            $p=1;
        }
		$pnum=50;
		//$start=($p-1)*$pnum;
		$start=0;
		$where=" islive='1' and liveclassid={$liveclassid} ";
        
		if($p!=1){
			$endtime=$_SESSION['getClassLive_starttime'];
            if($endtime){
                $where.=" and starttime < {$endtime}";
            }
			
		}
		$last_starttime=0;
		$result=DI()->notorm->live
				->select($this->live_fields)
				->where($where)
				->order("starttime desc")
				->limit(0,$pnum)
				->fetchAll();	
		foreach($result as $k=>$v){
			$v=handleLive($v);
            $result[$k]=$v;
		}		
		if($result){
            $last=end($result);
			$_SESSION['getClassLive_starttime']=$last['starttime'];
		}

		return $result;
    }
    
}
