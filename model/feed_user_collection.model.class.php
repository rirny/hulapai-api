<?php
// 微博收藏
class Feed_user_collection_model Extends Model
{
	protected $_db = NULL;
	protected $_table = 'ts_feed_collection';
	protected $_key = 'collection_id';
	protected $_table_user = 't_user';
		
	public function __construct(){
		parent::__construct();	
	}
	
	/**
	 * 获取微博用户收藏数
	 */
	public function getUserCollectionNum($uid){
		$result = db()->fetchRow("SELECT COUNT(*) AS n FROM $this->_table WHERE uid='$uid'");
		return $result['n'] ? $result['n'] : 0;
	}
	
	/**
	 * 获取微博用户收藏微博id字符串
	 */
	public function getUserCollectionFeedIdsStr($uid,$offset=0,$pagesize=20){
		$tmpSql = "select feed_id from $this->_table where uid='$uid' ORDER BY feed_id DESC";
		if($pagesize){
			$tmpSql .= " LIMIT $offset,$pagesize";
		}
		$sql = "SELECT GROUP_CONCAT(t.feed_id) as feed_ids FROM ($tmpSql) t";
		$result = db()->fetchRow($sql);
		return $result['feed_ids'] ? $result['feed_ids'] : '';
	}
}
