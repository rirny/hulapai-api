<?php
// 好友
class School_teacher_model Extends Model
{
	protected $_table = 't_school_teacher';
	protected $_key = 'id';
		
	public function __construct(){
		parent::__construct();
	}
	
	public function is_exists($school, $teacher)
	{
		if(!$school || !$$teacher) return false;
		$res = $this->getRow(array('teacher' => $teacher, 'school' => $school));
		if($res) return true;
		return false;
	}
	
	public function add($school, $teacher)
	{
		if($this->is_exists($school, $teacher)) return false;		
		$res = $this->getRow(array('school' => $school, 'teacher' => $teacher));
		if($res) return false;
		return $this->insert(array(
			'teacher' => $teacher,
			'school' => $school,
			'create_time' => time()
		));
	}
	
	public function getTeacherStudentIds($school,$teacher,$str=false){
		//获取机构下老师的学生
		$sql = "SELECT DISTINCT student FROM t_course_student WHERE event IN (SELECT a.id FROM t_event a LEFT JOIN t_course_teacher b ON a.id = b.event WHERE a.school= $school AND b.teacher = $teacher)";
		$result = db()->fetchAll($sql);
		if(!$result) return false;
		$studentIds = array();
		foreach($result as $_result){
			$studentIds[] = $_result['student'];
		}
		return $str ? implode(',',$studentIds):$studentIds;
	}
}
