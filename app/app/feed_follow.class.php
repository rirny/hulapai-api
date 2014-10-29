<?php
/**
 * 微博关注
 */
class Feed_follow_Api extends Api
{
	public function __construct(){
		parent::_init();
	}

	/** 
	 * 列表
	*/
	public function getList()
	{
		$type= Http::post('type', 'trim');
		if(!in_array($type,array('follower','following')))
		{
			out(0, '参数错误 type');
		}
		$uid= Http::post('uid', 'int',0);
		$uid = $uid ? $uid : $this->uid;
		$page = Http::post('page','int',0);
		$page = !$page || $page <= 0 ? 1 : $page;
		$_Feed_User_Follow = load_model('feed_user_follow');
		$total = $type == 'follower' ? $_Feed_User_Follow->getUserFollowerNum($uid) : $_Feed_User_Follow->getUserFollowingNum($uid);
		$pagesize = 20;
		$pages = ceil($total/$pagesize);
		$pages = $pages <= 0 ? 1 : $pages;
		$offset = ($page-1)*$pagesize;

		$uids = $type == 'follower' ? $_Feed_User_Follow->getUserFollowerUidStr($uid,$offset,$pagesize) : $_Feed_User_Follow->getUserFollowingUidStr($uid,$offset,$pagesize);
		$follows = array();
		if($uids){
			$follows = load_model('user')->getBaseUsers($uids);
			if($follows){
				foreach($follows as &$follow)
				{
					$follow['follower'] = $_Feed_User_Follow->getUserFollowerNum($follow['uid']);
					$follow['following'] = $_Feed_User_Follow->getUserFollowingNum($follow['uid']);
				}
			};
		}
		out(1, '', array('follows'=>$follows, 'page'=>array('page'=>$page,'total'=>$total,'size'=>$pagesize,'pages'=>$pages)));
	}	
	
	/**
	 * 微博关注
	 */
	public function add()
	{
		$fid= Http::post('fid', 'int', 0);
		if(!$fid)
		{
			out(0, '参数错误 fid');
		}
		$_Feed_User_Follow = load_model('feed_user_follow');
		$_Feed_User_Data = load_model('feed_user_data');
		$follow = $_Feed_User_Follow->getRow(array('uid'=>$this->uid,'fid'=>$fid));
		if($follow)
		{
			out(0, '你已经关注过此TA');
		}
		db()->begin();
		try{
			$nowTime = time();
			$follow = array(
				'uid'=>$this->uid,
				'fid'=>$fid,
				'ctime'=>$nowTime,
			);
			if(!$_Feed_User_Follow->insert($follow)) throw new Exception('关注失败！');
			//用户关注+1
			if(!$_Feed_User_Data->increment('value', array('uid'=>$this->uid,'key'=>'feed_following_count'), 1))
			{
				$ts_user_data = array(
					'uid'=>$this->uid,
					'key'=>'feed_following_count',
					'value'=>1,
					'mtime'=>$nowTime,
				);
				if(!$_Feed_User_Data->insert($ts_user_data)) throw new Exception('关注失败！');
			}
			//对方粉丝+1
			if(!$_Feed_User_Data->increment('value', array('uid'=>$fid,'key'=>'feed_follower_count'), 1))
			{
				$ts_user_data = array(
					'uid'=>$fid,
					'key'=>'feed_follower_count',
					'value'=>1,
					'mtime'=>$nowTime,
				);
				if(!$_Feed_User_Data->insert($ts_user_data)) throw new Exception('关注失败！');
			}
			//推送
			$to = $fid;
			push('db')->add('H_PUSH',array(
				'app' => $this->app,'act' => $this->act,'from' =>$this->uid,'to'=>$to,'type' => 1,'ext'=> array('uid'=>$this->uid)
			));	
			db()->commit();
			out(1, '关注成功');
		}catch(Exception $e)
		{
			db()->rollback();
			Out(0, $e->getMessage());
		}
		
		
	}
	
	/**
	 * 微博取消关注
	 */
	public function delete()
	{
		$fid= Http::post('fid', 'int', 0);
		if(!$fid)
		{
			out(0, '参数错误 fid');
		}
		$_Feed_User_Follow = load_model('feed_user_follow');
		$_Feed_User_Data = load_model('feed_user_data');
		$follow = $_Feed_User_Follow->getRow(array('uid'=>$this->uid,'fid'=>$fid));
		if(!$follow)
		{
			out(0, '你没有关注过此TA');
		}
		db()->begin();
		try{
			if(!$_Feed_User_Follow->delete(array('uid'=>$this->uid,'fid'=>$fid),true)) throw new Exception('取消关注失败！');
			//用户关注-1
			if(!$_Feed_User_Data->decrement('value', array('uid'=>$this->uid,'key'=>'feed_following_count'), 1)) throw new Exception('取消关注失败！');
			//对方粉丝-1
			if(!$_Feed_User_Data->decrement('value', array('uid'=>$fid,'key'=>'feed_follower_count'), 1)) throw new Exception('取消关注失败！');
			db()->commit();
			out(1, '取消关注成功');
		}catch(Exception $e)
		{
			db()->rollback();
			Out(0, $e->getMessage());
		}
	}
}