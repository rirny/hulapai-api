<?php
// 消息
class Message_model Extends Model
{
	protected $_db = NULL;
	protected $_table = 't_message';
	protected $_key = 'id';
	protected $_table_user = 't_user';
		
	public function __construct(){
		parent::__construct();	
	}
	
	
	/**
	 * 获取列表
	 */
	public function getList($uid,$type=0,$offset=0,$pagesize=20){
        is_array($type) && $type = join(",", $type);
		$sql = "select * from $this->_table where `to` = $uid and `type` in($type)  order by create_time desc limit $offset,$pagesize";
		$list = db()->fetchAll($sql);
		return $list;
	}
}
