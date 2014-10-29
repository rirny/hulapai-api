<?php
// 学生课程
class Student_course_model Extends Model
{
	protected $_table = 't_course_student';
	protected $_key = 'id';

	private $detail = Null;
	private $simple = Null;
	
	public function __construct(){
		parent::__construct();
	}

	public function get_student($param, $out = false)
	{	
		$where = $this->whereExp($param);
		if(!$where) return false;
		$result = db()->fetchCol('select student from ' . $this->_table . " where " . $where);
		if($result && $out)
		{
			foreach($result as $key => $item)
			{
				$result[$key] = load_model('student')->getRow('`user`=' . $item, true);
			}
		}
		return $result;
	}

	// compact('start_date', 'end_date', 'student', 'attend','leave','absence','ala', 'comment', 'pid', 'teacher');
	public function getList($param=array(), $out=false)
	{
		$result = array();	
		extract($param);		
		$sql = "select e.id,e.pid,r.student,e.course,e.grade,e.school,e.start_date,e.end_date,e.length,e.is_loop,e.rec_type,e.teacher,e.`lock`,e.modify_time,e.creator";
        $sql .= ",r.start_date start,r.end_date end,r.remark,r.color,r.fee,r.attend,r.leave,r.absence,r.commented,r.attended";
        $sql .= " from " .$this->_table. " r left join t_event e on r.`event`=e.id";
		$where = " where e.`status`=0 And r.`status`=0";
        empty($pid) || $where.= " And e.pid='{$pid}'";
		$student && $where .= is_array($student) ? " And r.student in(" . join(",", $student) .")" : " And r.student='" . $student . "'";
		$end_date && $where .= " And e.start_date<'" . $end_date . "' And r.start_date<'" . $end_date ."'";
		$start_date && $where .= "(r.end_date>'0000-00-00 00:00:00' and r.end_date>'{$start_date}') Or (r.end_date='0000-00-00 00:00:00' and e.end_date>'{$start_date}')";// " And e.end_date>'" . $start_date . "'";
		if(isset($ala) && $ala > 0) $where .= " And r.attend=$attend And r.leave=$leave And r.absence=$absence";
		empty($coures) || $where .= " And e.coures='" . $coures . "'"; // 课程类型	
		empty($teacher) || $where .= " And e.teacher='" . $teacher . "'"; // 老师
		empty($school) || $where .= " And e.school ='" . $school . "'";
		
		$result = db()->fetchAll($sql . $where . " order by e.start_date");
		if($result && $out)
		{
			foreach($result as $key=>$item)
			{
                $item['start'] == '0000-00-00 00:00:00' || $item['start_date'] = $item['start'];
                $item['end'] == '0000-00-00 00:00:00' || $item['end_date'] = $item['end'];
                unset($item['start'], $item['end']);
				$item = $this->Format($item);
				$item['teacher'] = load_model('user')->getRow($item['teacher'], true, 'id,firstname,lastname,nickname,avatar');
				$result[$key] = $item;
			}
		}
        if($pid)
        {            
            return array(
                'parent' => db()->fetchRow($sql . " where e.id='{$pid}'"),
                'childs' => $result
            );                      
        }
		return $result;
	}

	public function getSimpleEventList($param=array(), $out=false)
	{
		$result = array();	
		extract($param);
		$sql = "select e.id as _id,e.pid,e.length,e.is_loop,e.rec_type,r.remark,r.color,r.fee,e.start_date,e.end_date,e.school";
        $sql .= ",r.start_date start,r.end_date end,e.teacher,e.attended";
        $sql .= " from " .$this->_table. " r left join t_event e on r.`event`=e.id";
		$where = " where e.`status`=0 And r.`status`=0";
		empty($tm) || $where .= " And e.modify_time>'" . date('Y-m-d H:i:s', $tm) . "'";
		$student && $where .= is_array($student) ? " And r.student in(" . join(",", $student) .")" : " And r.student='" . $student . "'";
		$end_date && $where .= " And e.start_date<'" . $end_date . "' And r.start_date<'" . $end_date ."'";
		$start_date && $where .= "(r.end_date>'0000-00-00 00:00:00' and r.end_date>'{$start_date}') Or (r.end_date='0000-00-00 00:00:00' and e.end_date>'{$start_date}')";// " And e.end_date>'" . $start_date . "'";
		$result = db()->fetchAll($sql . $where . " order by e.start_date");
		if($result && $out)
		{
			foreach($result as & $item)
			{	
				$item['start'] == '0000-00-00 00:00:00' || $item['start_date'] = $item['start'];
                $item['end'] == '0000-00-00 00:00:00' || $item['end_date'] = $item['end'];
				// pid
				$item['pid'] = 0;
				unset($item['start'], $item['end']);				
				$item = array_values($item);
			}
		}
		return $result;
	}
	
	// 学生比较
	public function compare($event, $students, $current=false)
	{
		$new = $lost = $keep = array();		
		if($current) // 已结束的学员不取
		{
			$sql = "select student from {$this->_table} r left join t_event e on r.`event`=e.id where e.`status`=0 And e.`id`={$event} And e.is_loop=1";
			$sql.= " And (r.end_date='0000-00-00 00:00:00' Or r.end_date>=e.start_date)"; // 结束时间必须大于循环课程的时间
			$resource = db()->fetchCol($sql);
		}else
		{
			$resource = $this->get_student(array('event' => $event));
		}
		if(!$resource && !$students) return array();
		is_array($students) || $students = explode(',', $students);		
		$new = array_values(array_diff($students, $resource));
		$lost = array_values(array_diff($resource, $students));
		$keep = array_values(array_intersect($resource, $students));
		return compact('new', 'lost', 'keep');
	}

	public function insert($data, $student=0)
	{
		if(empty($data) || !$student) return false;		
		$event = array(
			'student' => $student,
			'event' => $data['id'],            
			'remark'=> $data['text'],			
			'color' => $data['color'],
			'status' => $data['status'],	
		);
		
		$fee = !isset($data['fee']) || !is_numeric($data['fee']) ? 100 : $data['fee'];
		// 循环课程时间变更
		if($data['is_loop'])
		{
			$res = load_model('event')->getRow($data['id']);
			$data['start_date'] == $res['start_date'] || $event['start_date'] = $data['start_date'];
			$data['end_date'] == $res['end_date'] || $event['end_date'] = $data['end_date'];		
		}
        
        // 班级、学生课程关系 2013-09-22
        if($data['grade'])
        {          
            $_Event_grade = load_model('event_grade', array('table' => 'event_grade')); 
            
            $res = $_Event_grade->insert(array(
                'grade' => $data['grade'], 
                'student' => $student, 
                'event' => $data['id'],
                'teacher' => $data['teacher']
            ));
            if(!$res) return false; 
        }
        
		if(!empty($data['pid'])) // 循环课程
		{
			$parent = $this->getRow(array('event' => $data['pid'], 'student' => $student));
			if(!$parent) return false;
			$event['remark'] = $parent['remark'];
			$event['color'] = $parent['color'];
			$fee || $fee = $parent['fee'];
		}
		$event['fee'] = $fee;
		return parent::insert($event);
	}
	
	public function create($event, $student)
	{		
		if(empty($event) || !$student) return false;
		$res = $this->getRow(array('event' => $event['id'], 'student' => $student));		
		if(!$res)
		{
			return $this->insert($event, $student);
		}
		return true;
	}

	public function rec_all($pid, $student, $out=false)
	{
		$result = array();
		if(!$pid || !$student) return $result;       
		$relation = $this->getRow(array('event' => $pid, 'student' => $student, 'status' => 0), false, 'student,attend,`leave`,absence,color,remark,commented');		
		$event = load_model('event')->getRow($pid);		
		if(!$event['is_loop'] || empty($relation)) return $result;       
		import('repeat');				
		$rec = Repeat::resolve($event['start_date'], $event['end_date'], $event['rec_type'], $event['length']);       
		foreach($rec as $item)
		{
			$res = load_model('event')->getRow(array('pid' => $pid, 'length' => $item['length']));			
			if($res)
			{
				$item = $res;
				$relation = $this->getRow(array('event' => $res['id'], 'student' => $student), false, 'student,attend,`leave`,absence,color,remark,commented');
			}else{
				$item['id'] = $pid . "#" . $item['length'];
				$item['pid'] = $pid;
				$item = array_merge($event, $item);                
			}
			if($out) {
				$item = $this->Format($item);
				$item['relation'] = $this->Format($relation);				
			}
			$result[] = $item;
		}
		return $result;
	}


	// 删除关系课程
    public function cut_relation($event, $relation, $pid=0, &$push=array())
    {
        if(empty($event) || empty($relation)) return false;
        $_Event = load_model('event');        
        $this->delete($relation['id'], true);  // 删除关系
        $tm = time();             
        $parents = array();        
        if(strtotime($event['start_date']) < $tm)   // 已发生的课程
        {
            // 已发生的复制    
            $data = $event;
            $data['pid'] = $pid;
            unset($data['id']);
            unset($relation['id'], $relation['modify_time']);
            if($event['is_loop'] == 1)
            {
                // 处理子课程
                $relation['end_date'] != '0000-00-00 00:00:00' && $data['end_date'] = $event['end_date'];                    
                $relation['start_date'] != '0000-00-00 00:00:00' && $data['start_date'] = $event['start_date']; 
                if(strtotime($event['end_date']) > $tm)
                {
                    $recent = $_Event->recent($event);
                    $data['end_date'] = $recent['end_date'];
                    $relation['end_date'] = '0000-00-00 00:00:00';
                }
                $id = $relation['event'] = $_Event->insert($data);                
                $sql = "select e.id,r.id rid from t_course_student r left join t_event e on r.event=e.id where e.`status`=0 AND r.`status`=0 AND e.creator={$event['teacher']} AND r.student={$relation['student']} and e.pid={$event['id']}";
                $childs = db()->fetchAll($sql);                
                if($childs)
                {                   
                    foreach($childs as $key=>$item)
                    {
                        $E = $_Event->getRow($item['id']);
                        $R = $this->getRow($item['rid']);   
                        $this->cut_relation($E, $R, $id, $push); // 切断关系
                    }
                }
            }else{                
                $id = $relation['event'] = $_Event->insert($data);
            }
            if(!$id) return false;            
            $relation['source'] = 2; // 断联系标识           
            $rid = parent::insert($relation);            
            if(!$rid) return false;
            array_push($push ,array($event['id'], $id));
        }      
        if(empty($result)) return true;
        return $result;
    }   
}
