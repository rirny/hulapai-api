<?php
// 微博关注
class Feed_user_follow_model Extends Model
{
	protected $_db = NULL;
	protected $_table = 'ts_feed_user_follow';
	protected $_key = 'follow_id';
	protected $_table_user = 't_user';
		
	public function __construct(){
		parent::__construct();	
	}

	/**
	 * 获取微博用户关注数
	 */
	public function getUserFollowingNum($uid){
		$result = db()->fetchRow("SELECT COUNT(*) AS n FROM $this->_table WHERE uid='$uid'");
		return $result['n'] ? $result['n'] : 0;
		
	}
	
	/**
	 * 获取微博用户关注uid字符串
	 */
	public function getUserFollowingUidStr($uid,$offset=0,$pagesize=20){
		$tmpSql = "select fid from $this->_table where uid='$uid'";
		if($pagesize){
			$tmpSql .= " LIMIT $offset,$pagesize";
		}
		$sql = "SELECT GROUP_CONCAT(t.fid) as uids FROM ($tmpSql) t";
		$result = db()->fetchRow($sql);
		return $result['uids'] ? $result['uids'] : '';
	}
	
	
	/**
	 * 获取微博用户粉丝数
	 */
	public function getUserFollowerNum($uid){
		$result = db()->fetchRow("SELECT COUNT(*) AS n FROM $this->_table WHERE fid='$uid'");
		return $result['n'] ? $result['n'] : 0;
	}
	
	/**
	 * 获取微博用户粉丝id字符串
	 */
	public function getUserFollowerUidStr($uid,$offset=0,$pagesize=20){
		$tmpSql = "select uid from $this->_table where fid='$uid'";
		if($pagesize){
			$tmpSql .= " LIMIT $offset,$pagesize";
		}
		$sql = "SELECT GROUP_CONCAT(t.uid) as uids FROM ($tmpSql) t";
		$result = db()->fetchRow($sql);
		return $result['uids'] ? $result['uids'] : '';
	}

	// 获取老师列表+粉丝数据
	public function teacherFollow($page=1, $refresh=0, $uid=0,$perpage=20)
	{
		$key = "teacherListFollow";		
		$limit = (($page - 1) * $perpage) . "," . $perpage;
		$key .= md5($limit);
		$result = cache()->get($key);		
		if(!$result || $refresh)
		{
			$sql = "select u.id,u.firstname,u.lastname,u.hulaid,t.mind,count(f.uid) As follows from ts_feed_user_follow f left join t_user u on f.fid=u.id";
			$sql.= " Left join t_teacher t on u.id=t.`user`"; 
			$sql.= " where u.teacher=1 And (u.id<100 or u.id>200) group by f.fid order by follows Desc";
			$limit && $sql .= " Limit " . $limit;
			$result = db()->fetchAll($sql);
			if(!$result) return false;
			cache()->set($key, $result, 1800);
		}
		return $result;
	}
}
