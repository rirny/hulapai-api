<?php
// 老师 - 学生

class Teacher_student_model Extends Model
{
	protected $_table = 't_teacher_student';
	protected $_key = 'id';

	private $detail = Null;
	private $simple = Null;
	
	public function __construct(){
		parent::__construct();
	}


	
	/*
	 * @ type | teacher,student
	*/
	public function getList($id, $type='teacher', $out=false)
	{
		$result = array();
		if(!$id) return $result;
		$sql = "select e.*,r.classes,r.attend,r.leave,r.absence,r.study_date from " . $this->_table . " r";
		if($type == 'teacher')
		{
			$sql.= ' left join t_student e on r.`student`=e.id where r.`teacher`=' . $id;
		}else{
			$sql.= ' left join t_teacher e on r.`teacher`=e.id where r.`student`=' . $id;
		}		
		$result = db()->fetchAll($sql);
		if($result && $out){
			foreach($result as $key => $item){
				$result[$key] = $this->format($item);
			}
		}
		return $result;		
	}

	public function getOne($teacher, $student, $type='teacher', $out=false)
	{
		$result = array();
		if(!$teacher || !$student) return $result;
		$result = parent::getRow(array('teacher' => $teacher, 'student' => $student));
		if($result && $out)
		{
			$result = $this->Format($result);
			if($type == 'teacher')
			{
				$result['student'] = $this->Format(load_model('student')->getRow($result['student']));
			}else{
				$result['teacher'] = $this->Format(load_model('teacher')->getRow($result['teacher']));
			}
		}
		return $result;
	}

	public function add($teacher, $student)
	{
		if(!$teacher || !$student) return false;
		$res = $this->getRow(array('student' => $student, 'teacher' => $teacher, 'type' => 0));		
		if($res) return false;
		return $this->insert(array(
			'teacher' => $teacher,
			'student' => $student,
			'type'	=> 0,
			'create_time' => time()
		));
	}

}
