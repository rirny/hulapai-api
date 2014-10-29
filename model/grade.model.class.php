<?php
class Grade_model Extends Model
{
	protected $_table = 't_grade';
	protected $_key = 'id';

	private $detail = Null;
	private $simple = Null;
	
	public function __construct(){
		parent::__construct();
	}

	public function get_students($grade, $out=false)
	{
		$result = array();
		if(!$grade) return $result;		
		$result = db()->fetchAll("select `grade`, student,creator from t_grade_student where `grade`='" . $grade . "'");
		if($result && $out)
		{
			foreach($result as $key=>$item)
			{				
				// $item['grade'] = $this->getRow($item['grade'], true, 'id, name');				
				//$item['student'] = load_model('student')->getRow($item['student'], true, 'id, name, nickname, avatar');				
				$result[$key] = $item;
			}
		}		
		return $result;
	}
	
	// 获取班级下的单个学生
	public function get_student($grade, $student, $out=false)
	{
		$result = array();
		if(!$grade || !$student) return $result;
		$result = db()->fetchRow("select `grade`,student from `t_grade_student` where `grade`='" . $grade . "' And student='" .$student. "'");
		if($result && $out)
		{
			$result['grade'] = $this->getRow($result['grade'], true, 'id, name');
			$result['student'] = load_model('student')->getRow($result['student'], 'id, name, nickname, avatar');
		}
		return $result;
	}
	
	public function delete($param, $force=false)
	{		
		if(!$param || !is_numeric($param)) return false;           
		$res = parent::delete($param, $force);
		if(!$res) return false;
		return true;
	}
	
	// 移除学生
	public function remove_student($grade, $student, $user)
	{
		if(!$grade || !$student || !$user) return false;       
        $_Event_grade = load_model('event_grade', array('table' => 'event_grade'));
        $_Event_student = load_model('student_course');
        $_Event = load_model('event');
        $events = $_Event_grade->getAll(array('grade' => $grade, 'student' => $student));       
        $push = array();   
        foreach($events as $item)
        { 
			$event = $_Event->getRow($item['event']);
			if($event['pid'] == 0)
			{
				$relation = $_Event_student->getRow(array('event' => $item['event'], 'student' => $student));
				$_Event_student->cut_relation($event, $relation, 0, $push);            
			}
        }
        $res = load_model('student')->push($student, array(
            'app' => 'grade', 'act' => 'delete', 'from' => $user, 'type' => 0,
            'character' => 'teacher', 'ext' => array('event' => $push, 'grade' => $grade)
        ));        
        if(!$res) return false;
		$res = $_Event_grade->delete(array('grade' => $grade, 'student' => $student), true); // 删除关系  
        load_model('grade_student', array('table' => 'grade_student'))->delete(array('grade' => $grade, 'student' => $student), true); // 删除关系
        return true;
	}
	// 是否在班级
	public function student_exists($grade, $student)
	{
		if(!$grade || !$student) return false;
		$res = db()->fetchRow("select id from `t_grade_student` where `grade`='" . $grade . "' And student='" .$student. "'");
		if($res) return true;
		return false;
	}
	// 添加学生
    // @grade
    // @student
	public function add_student($grade, $student, $user)
	{
		if(!$grade || !$student || !$user) return false;        
        $_Event_grade = load_model('event_grade', array('table' => 'event_grade'));
        $_Event_student = load_model('student_course');
        $_Event = load_model('event');       
        // 加入到班级
        $create_time = time();
        $creator = $user;
		$res = db()->insert('t_grade_student', compact('grade', 'student', 'creator', 'create_time'));        
        if(!$res) return false;        
        // 班级课程
        $date = date('Y-m-d H:i:s', $create_time); // 所有未结束的课程
        $events = $_Event->getAll(array('grade' => $grade, 'end_date,>' => $date, 'status' => 0)); // 未结束
        foreach($events as $event)
        {
            if($event['is_loop'])
            {
                $recent = $_Event->recent($event, 'right');
                $event['start_date'] = $recent['start_date']; // 开始时间                           
            }
			
			if($_Event_student->getRow(array('event' => $event['id'], 'student' => $student))) continue;

            $res = $_Event_student->insert($event, $student); // 增加学生课程
            if(!$res) return false;
            // 增加学生、课程和班级关系
            $res = $_Event_grade->insert(array(
                'student' => $student,
                'grade' => $grade,
                'teacher' => $user,
                'event' => $event['id']
            ));
            if(!$res) return false;
            // 推送
            if($event['pid'] == 0)
            {
                $res = load_model('student')->push($student, array(
                    'app' => 'event', 'act' => 'add', 'from' => $creator, 'type' => 2,
                    'character' => 'teacher', 'ext' => array('event' => $event['id'])
                ));
                if(!$res) return false;
            }
        }
        return true;
	}
}