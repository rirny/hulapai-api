<?php
class Vote_model Extends Model
{
	protected $_table = 't_vote';
	protected $_key = 'id';	

	
	public function __construct(){
		parent::__construct();
	}
	
	
	/**
	 * 获取列表
	 */
	public function getList($uid,$offset=0,$pagesize=20){
		$sql = "select * from $this->_table where `creator` = $uid  order by create_time desc limit $offset,$pagesize";
		$list = db()->fetchAll($sql);
		return $list;
	}	 
}