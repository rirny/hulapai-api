<?php
/**
 * 微博收藏
 */
class Feed_collection_Api extends Api
{
	public function __construct(){
		parent::_init();
	}

	/** 
	 * 收藏列表
	*/
	public function getList()
	{
		$uid= Http::post('uid', 'int',0);
		$uid = $uid ? $uid : $this->uid;
		$page = Http::post('page','int',0);
		$page = !$page || $page <= 0 ? 1 : $page;
		$_Feed_User_Collection = load_model('feed_user_collection');
		$total = $_Feed_User_Collection->getUserCollectionNum($uid);
		$pagesize = 20;
		$pages = ceil($total/$pagesize);
		$pages = $pages <= 0 ? 1 : $pages;
		$offset = ($page-1)*$pagesize;

		$feed_ids = $_Feed_User_Collection->getUserCollectionFeedIdsStr($uid,$offset,$pagesize);
		$feeds = array();
		if($feed_ids){
			$feeds = load_model('feed')->getFeedsByFeedIds($feed_ids);
		}
		out(1, '', array('feeds'=>$feeds, 'page'=>array('page'=>$page,'total'=>$total,'size'=>$pagesize,'pages'=>$pages)));
	}	
	
	/**
	 * 微博收藏
	 */
	public function add()
	{
		$feed_id= Http::post('feed_id', 'int', 0);
		if(!$feed_id)
		{
			out(0, '参数错误 feed_id');
		}
		$_Feed= load_model('feed');
		$feed = $_Feed->getRow(array('feed_id'=>$feed_id));
		if(!$feed)
		{
			out(0, '微博不存在');
		}
		$_Feed_User_Collection = load_model('feed_user_collection');
		if($_Feed_User_Collection->getRow(array('uid'=>$this->uid,'feed_id'=>$feed_id)))
		{
			out(0, '已收藏过');
		}
		db()->begin();
		try{
			$collection = array(
				'uid'=>$this->uid,
				'feed_id'=>$feed_id,
				'ctime'=>time(),
			);
			$collection_id = $_Feed_User_Collection->insert($collection);
			if(!$collection_id) throw new Exception('收藏失败！');
			if(!$_Feed->increment('collect_count', array('feed_id'=>$feed_id), 1)) throw new Exception('收藏失败！');
			db()->commit();
			out(1, '收藏成功');
		}catch(Exception $e)
		{
			db()->rollback();
			Out(0, $e->getMessage());
		}
	}
	
	
	/**
	 * 微博取消收藏
	 */
	public function delete()
	{
		$feed_id= Http::post('feed_id', 'int', 0);
		if(!$feed_id)
		{
			out(0, '参数错误 feed_id');
		}
		$_Feed= load_model('feed');
		$feed = $_Feed->getRow(array('feed_id'=>$feed_id));
		if(!$feed)
		{
			out(0, '微博不存在');
		}
		$_Feed_User_Collection = load_model('feed_user_collection');
		if(!$_Feed_User_Collection->getRow(array('uid'=>$this->uid,'feed_id'=>$feed_id)))
		{
			out(0, '未收藏过');
		}
		db()->begin();
		try{
			if(!$_Feed_User_Collection->delete(array('uid'=>$this->uid,'feed_id'=>$feed_id),true)) throw new Exception('取消收藏失败！');
			if(!$_Feed->decrement('collect_count', array('feed_id'=>$feed_id), 1)) throw new Exception('取消收藏失败！！');
			db()->commit();
			out(1, '取消收藏成功');
		}catch(Exception $e)
		{
			db()->rollback();
			Out(0, $e->getMessage());
		}
	}
}