<?php
class Student_model Extends Model
{
	protected $_table = 't_student';
	protected $_key = 'id';
	
	public function __construct(){
		parent::__construct();
	}
	
	// 获取老师
	public function get_teacher($student)
	{
		$result = array();
		if(!$student) return $result;
		$res = db()->fetchAll('select * from t_teacher_student where student=' . $student);
		if($res) $result = (object)$res;
		return $result;
	}
	
	
	public function push($student, $data, $uid=0)
	{   
		if(!$student || empty($data)) return false;		
        $res = load_model('user_student')->get_parents($student, false);          
		if($res){
			foreach($res as $key=>$item)
			{  
                if($uid && $item['user'] == $uid) continue;
				$data['to'] = $item['user'];
				$data['student'] = $student; // $this->getRow($student, true, 'id,nickname,avatar');               
				push('db')->add('H_PUSH', $data);
			}
		}
        // 没有家长
		return true;
	}
}
