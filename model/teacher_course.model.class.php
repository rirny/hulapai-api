<?php
// 老师 - 学生

class Teacher_course_model Extends Model
{
	protected $_table = 't_course_teacher';
	protected $_key = 'id';

	private $detail = Null;
	private $simple = Null;
	
	public function __construct(){
		parent::__construct();
	}

	public function get_teacher($param, $out = false)
	{	
		$where = $this->whereExp($param);
		if(!$where) return false;		
		$result = db()->fetchCol('select teacher from ' . $this->_table . " where " . $where);
		if($result && $out)
		{
			foreach($result as $key => $item)
			{
				$result[$key] = load_model('teacher')->getRow('`user`=' . $item, true);
			}
		}
		return $result;
	}
	
	// 老师比较
	public function compare($event, $teachers)
	{
		$new = $lost = $keep = array();		
		$resource = $this->get_teacher(array('event' => $event));
		if(!$resource && !$teachers) return array();
		is_array($teachers) || $teachers = explode(',', $teachers);		
		$new = array_diff($teachers, $resource);
		$lost = array_diff($resource, $teachers);
		$keep = array_intersect($resource, $teachers);
		return compact('new', 'lost', 'keep');
	}
	
	public function insert($data, $teacher=0)
	{		
		if(empty($data) || $teacher ==0) return false;
		$res = $this->getRow(array('event' => $data['id'], 'teacher' => $teacher));
		if($res) return $res['id']; // 已经存在		
		$event = array(
			'teacher' => $teacher,
			'event' => $data['id'],
			'remark'=> $data['text'],			
			'color' => $data['color'],
			'priv' => isset($data['priv']) ? $data['priv'] : 0,
			'status' => $data['status'],
		);		
		if(!empty($data['pid']))
		{
			$where = array('event' => $data['pid']);
			if($teacher) $where['teacher'] = $teacher;
			$parent = $this->getRow($where);
			if(!$parent) return false;
			$event['remark'] = $parent['remark'];
			$event['priv'] = $parent['priv'];
			$event['color'] = $parent['color'];
			// $event['pid'] = $event['pid'];
		}			
		return parent::insert($event);
	}
	
	// operator 0增加 1减
	public function stat($event, $teacher, $operator=0)
	{
		if(!empty($event) || !$teacher) return false;		
		extract($event);		
		if(!empty($rec_type) && empty($pid)) // 循环课程
		{
			$classes = 0;
			$repeat = Repeat::resolve($start_date, $end_date, $rec_type, $length);			
			foreach($repeat as $item)
			{
				$start = strtotime($item['start_date']);
				if($start > time()) // 大于当前时间
				{
					if($operator == 1)
					{
						$classes--;
					}else
					{
						$classes++;
					}					
				}
			}			
		}else{
			$classes = $operator ? -1 : 1;			
		}
		$res = load_model('teacher')->increment('classes', $teacher, $classes);// getRow($student);
		return true;
	}
	

	public function rec_all($pid, $teacher, $out=false)
	{
		$result = array();
		if(!$pid || !$teacher) return $result;
		$relation = $this->getRow(array('event' => $pid, 'teacher' => $teacher, 'status' => 0), false, 'teacher,priv,color,remark');       
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
				$item['relation'] = $this->getRow(array('event' => $res['id'], 'teacher' => $teacher), false, 'teacher,priv,color,remark');
			}else{
				$item['id'] = $pid . "#" . $item['length'];
				$item['pid'] = $pid;
				$item = array_merge($event, $item);
				$item['relation'] = $relation;
			}
			if($out) $item = $this->Format($item);
			$result[] = $item;
		}       
		return $result;
	}

	public function getList($param=array(), $out=false)
	{
		$result = array();	
		extract($param);		
		$sql = "select e.id,e.pid,e.course,e.grade,e.school,e.start_date,e.end_date,e.length,e.is_loop,e.rec_type,e.attend,e.leave,e.absence,e.attended,e.commented,e.`lock`,e.modify_time,e.creator";
        $sql.= ",r.teacher as 'user',r.remark,r.color,r.priv from " .$this->_table. " r left join t_event e on r.`event`=e.id";
		$where = " where e.`status`=0";        
        empty($pid) || $where .= " And e.pid='{$pid}'";
		$end_date && $where .= " And e.start_date<'" . $end_date . "'";
		$start_date && $where .= " And e.end_date>'" . $start_date . "'";	
		$school && $where .= " And e.school ='" . $school . "'";
		empty($teacher) || $where .= " And r.teacher='" . $teacher . "'"; // 课程老师
		empty($coures) || $where .= " And e.coures='" . $coures . "'"; // 课程类型
		if(isset($ala) && $ala > 0) $where .= " And e.attend=$attend And e.leave=$leave And e.absence=$absence";        
		$result = db()->fetchAll($sql . $where . " order by e.start_date");
		if($result && $out)
		{
			foreach($result as $key=>$item)
			{				            
				$item['teacher'] = load_model('user')->getRow($item['user'], true, 'id,firstname, lastname,hulaid,nickname,avatar');
                $item['students'] = load_model('student_course')->getColumn(array('event' => $item['id'], 'status' => 0), 'student');
				$result[$key] = $item;
                $item = $this->Format($item);    
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
		$sql = "select e.id as _id,e.pid,e.length,e.is_loop,e.rec_type,r.remark,r.color,e.start_date,e.end_date,e.school,e.grade,e.attended,e.commented,e.`lock`";
        $sql.= ",r.start_date 'start',r.end_date 'end',e.attended from " .$this->_table. " r left join t_event e on r.`event`=e.id";
		$where = " where e.`status`=0";
		$end_date && $where .= " And e.start_date<'" . $end_date . "'";
		$start_date && $where .= " And e.end_date>'" . $start_date . "'";		
		empty($teacher) || $where .= " And r.teacher='" . $teacher . "'";
		empty($tm) || $where .= " And e.modify_time>'" . date('Y-m-d H:i:s', $tm) . "'";
		$result = db()->fetchAll($sql . $where . " order by e.start_date ");
		if($result && $out)
		{
			foreach($result as & $item)
			{				            
				$item['start'] == '0000-00-00 00:00:00' || $item['start_date'] = $item['start'];
                $item['end'] == '0000-00-00 00:00:00' || $item['end_date'] = $item['end'];
				unset($item['start'], $item['end']);
				// $item['teacher'] = load_model('user')->getRow($item['user'], true, 'id,firstname, lastname,hulaid,nickname,avatar');
                $item['students'] = load_model('student_course')->getColumn(array('event' => $item['_id'], 'status' => 0), 'student');
				$item['pid'] = 0;
				$item = array_values($item);
                // $item = $this->Format($item);    
			}
		}      
		
		//print_r(array_values($result));
		return $result;
	}


	public function create($event, $teacher='')
	{		
		if(empty($event) || !$teacher) return false;		
		$res = $this->getRow(array('event' => $event['id'], 'teacher' => $teacher));	
		if(!$res)
		{
			return $this->insert($event, $teacher);
		}
		return true;
	}
}
