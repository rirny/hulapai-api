<?php
class Cms_model Extends Model
{
	protected $_table = 'phpcms.v9_hulapai';
	protected $_table_data = 'phpcms.v9_hulapai_data';
	protected $_key = 'id';	

	
	public function __construct(){
		parent::__construct();
	}
	
	
	/**
	 * 获取帮助列表
	 */
	public function getHelpList($limit=20){
		$sql = "select a.id,a.title,b.content from $this->_table as a,$this->_table_data as b where a.id=b.id and a.catid=6 order by a.listorder asc,a.inputtime desc limit $limit";
		$list = db()->fetchAll($sql);
		return $list;
	}	 
}