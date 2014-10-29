<?php
// 好友
class School_student_model Extends Model
{
	protected $_table = 't_school_student';
	protected $_key = 'id';
		
	public function __construct(){
		parent::__construct();
	}

	public function getAll($param=array(), $limit='', $order='', $cache=false, $out=false, $field = '')
	{
		$result = paraent::getAll($param, $limit, $order, $cache, $out);
		if($result && $out)
		{
			foreach($result as $key => $item)
			{
				$item = $this->Format($item);
				// $item['friend'] = $this->Format(load_model('user')->getRow($item['friend']));
				$result[$key] = $item;
			}
		}
		return $result;
	}
	
	public function is_exists($school, $student)
	{
		if(!$school || !$$student) return false;
		$res = $this->getRow(array('student' => $student, 'school' => $school));
		if($res) return true;
		return false;
	}

	public function add($school, $student)
	{
		//if($this->is_exists($school, $student)) return false;		
		return $this->insert(array(
			'student' => $student,
			'school' => $school,
			'create_time' => time()
		));
	}	
}
