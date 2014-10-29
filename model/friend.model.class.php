<?php
// 好友
class Friend_model Extends Model
{
	protected $_table = 't_friend';
	protected $_table_user = 't_user';
	protected $_key = 'id';
		
	public function __construct(){
		parent::__construct();
	}
	
	/**
	 * 好友列表
	 */
	public function getFriends($uid)
	{
		$friends = db()->fetchAll("SELECT c.friend as user,u.nickname,u.avatar,u.hulaid,c.remark,c.group FROM $this->_table AS c LEFT JOIN $this->_table_user AS u ON c.friend=u.id WHERE c.user='$uid'");
		return $friends;
	}
	
	
	/**
	 * 获取好友uid字符串
	 */
	public function getFriendUidStr($uid,$offset=0,$pagesize=20){
		$tmpSql = "select friend from $this->_table where user='$uid'";
		if($pagesize){
			$tmpSql .= " LIMIT $offset,$pagesize";
		}
		$sql = "SELECT GROUP_CONCAT(t.friend) as users FROM ($tmpSql) t";
		$result = db()->fetchRow($sql);
		return $result['users'] ? $result['users'] : '';
	}
	
	public function is_friend($uid, $friend)
	{
		if(!$uid || !$friend) return false;
		$res = $this->getRow(array('user' => $uid, 'friend' => $friend));
		if($res) return true;
		return false;
	}

	public function add($user, $friend)
	{
		if($this->is_friend($user, $friend)) return false;	
		if($this->is_friend($friend, $user)) return false;
		$tm = time();		
		$res = $this->insert( array(
			'user' => $user,
			'friend' => $friend,
			'create_time' => $tm,					
		));
		if(!$res) return false;		
		return $this->insert( array(
			'user' => $friend,
			'friend' => $user,
			'create_time' => $tm,					
		));	
	}
}
