<?php
// 好友
class Apply_model Extends Model
{
	protected $_table = 't_apply';
	protected $_key = 'id';
		
	public function __construct(){
		parent::__construct();
	}
	
	/*
	* 1学生+老师		2老师+学生,	3机构+老师,	4老师+机构,	
	* 5好友申请,		6学生+机构,	7机构+学生
	*/
	
	public function getAll($param = Array(), $limit = '', $order = '', $cache = false, $out = false, $field = '')
	{	
		$result = parent::getAll($param, '', 'create_time Desc');
		if($result && $out)
		{
			$item = $this->Format($item);
			foreach($result as $key=>$item)
			{
				$result[$key] = $this->_format($item);
			}
		}
		return $result;
	}
	
	public function getRow($param, $out = false, $field='*', $order='')
	{		
		$result = parent::getRow($param, $out, $field, $order);		
		if($result && $out) $result = $this->_format($result);
		return $result;
	}
	
	public function _format(array $data)
	{
		if(empty($data)) return $data;		
		empty($data['student']) || $sutdent = $data['student'];
		if($data['student']) $data['student'] = load_model('student')->getRow($data['student'], true, 'id,name,nickname,avatar');		
		switch($data['type'])
		{	
			case 3:
			case 7:
				$data['from'] = load_model('school')->getRow($data['from'], true, 'id,name,pid,`type`,creator');
				break;
			case 8:	
				$data['relation'] = load_model('user_student')->get_relation($data['from'], $sutdent);
				$data['from'] = load_model('user')->getRow($data['from'], true, 'id,hulaid,nickname,firstname,lastname,gender,avatar');
				break;
			default :
				$data['from'] = load_model('user')->getRow($data['from'], true, 'id,hulaid,nickname,firstname,lastname,gender,avatar');
				break;
		}	
		return $data;
	}
}
