<?php
class Teacher_model Extends Model
{
	protected $_table = 't_teacher';
	protected $_key = 'id';

	protected $format_columns = array('id' => '_'); // 特殊处理	
	protected $unUses = array();// 不用的字段

	public function __construct(){
		parent::__construct();
	}

	public function insert($data)
	{
		$id = parent::insert($data);
		load_model('user')->update(array('teacher' => 1), $data['user']); // 更新用户是否拥有老师身份
		return $this->getRow($id);
	}

	public function getRow($param, $out = false, $field='*', $order='')
	{
		$result = parent::getRow($param, false, $field);		
		if($result && $out)
		{			
			// $course = load_model('course')->getAll(array('teacher' => $result['user']), '', '', false, true, $field);
			// isset($result['course']) $result['course'] = $course;
			$result = $this->Format($result);
		}		
		return $result;
	}

	// 学生的老师
	public function student_teacher($student, $out=false)
	{
		$result = array();
		if(!$student) return $result;
		$result = db()->fetchAll("select * from t_teacher_student where student=" . $student);
		if($result && $out){
			foreach($result as $key => $item){
				$result[$key] = $this->format($item);
			}
		}
		return $result;
	}

	public function stat()
	{
		// 课程
		$classes = 0;
		$comments = 0;
		$goods = 0;
		// 计算
		return compact($classes, $comments, $goods);
	}
	

	public function to_Array()
	{
		
	}

}
