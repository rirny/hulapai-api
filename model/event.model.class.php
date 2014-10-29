<?php
class Event_model Extends Model
{
	protected $_table = 't_event';
	protected $_key = 'id';
	
	public $object_name = 'event';
	public $object = Null;

	const DAY_TIME = 86400;

	// 特殊处理
	protected $format_columns = array(
	
	);
	// 不用的字段
	protected $unUses = array();
	
	public function __construct(){
		parent::__construct();
	}	
    
    // 取循环课程新近课程
    public function recent($event, $forward='left')
    {       
        if(empty($event['is_loop'])) return array();
		// 取此循环的第一节课
		$first = $this->getRow(array('pid' => $event['id']), false, '`length`', '`length` Asc');
		// $last = $this->getRow(array('pid' => $pid), false, '`length`', '`length` Desc');
		$start = strtotime($event['start_date']);
		if($first && $first['length'] < $start)
		{
			$time = date('H:i', $start);
			$date = date('Y-m-d', $first['length']);
			$event['start_date'] = $date . " " . $time;
		}
        import('repeat');					
        $rec = Repeat::resolve($event['start_date'], $event['end_date'], $event['rec_type'], $event['length']);				
        // $tm = strtotime(date('Y-m-d'));
        $tm = time();
		$result = array();
        if($forward == 'left') // 取最后一节已上课程
        {            
            while(list($key, $val) = each($rec))
            {
                if($val['length'] < $tm){
                    $result = $val;
                }
            }
        }else	// 取第一个未上课程
        {
            while(list($key, $val) = each($rec))
            {
                if($val['length'] >= $tm)
				{
					return $val;
				}
            }			
        }
        return $result;
    }
	
	// 距(date)最后一节已上课程
	public function last_happend($pid, $datetime='')
	{
		$datetime || $datetime = date('Y-m-d H:i');
		$this->getRow(array('pid' => $pid, 'length,<=' => $tm), false, 'id,start_date,end_date,`length`', '`length` Desc');
	}
	
	// 距(date)第一节未上课程
	public function first_happen($pid, $datetime='')
	{
		$datetime || $datetime = date('Y-m-d H:i');
		$this->getRow(array('pid' => $pid, 'length,>=' => $tm), false, 'id,start_date,end_date,`length`', '`length` Asc');
	}

	// 取所有已发生的
	public function happend($pid)
	{
		return $this->getAll(array('pid' => $pid, 'length,<=' => $tm), false, 'id,start_date,end_date,`length`', '`length` Desc');		
	}
	// 取所有未发生的
	public function unhappen($pid)
	{
		return $this->getAll(array('pid' => $pid, 'length,>=' => $tm), false, 'id,start_date,end_date,`length`', '`length` Desc');		
	}
    
    // 获取一个子课程
    public function get_child($event, $length)
    {
        if(empty($event['is_loop']) || $length < 1) return false;
        import('repeat');
        $rec = Repeat::resolve($event['start_date'], $event['end_date'], $event['rec_type'], $event['length']);				
        $tm = time();
        while(list($key, $val) = each($rec))
        {            
            if($val['length'] == $length)  return $val;
        }
        return false;
    }
    
    // 循环课程清理  
	public function rec_clear($pid, $whole=0)
	{         
		if(!$pid || !is_numeric($pid)) return false;      
		$_Teacher_course = load_model('teacher_course');
		$_Student_course = load_model('student_course');		
        
		$where = array('pid' => $pid, 'status' => 0);
        $tm = date('Y-m-d H:i:s');
		$whole || $where['start_date,>'] = $tm;        
		$remove = $this->getColumn($where, 'id');      
		if($remove)
		{
            $teacherCourse =  $_Teacher_course->getColumn(array('event,in' => $remove), 'id');
            $_Teacher_course->delete(array('id,in' => $teacherCourse), true);
            $studentCourse =  $_Student_course->getColumn(array('event,in' => $remove), 'id');
            $_Student_course->delete(array('id,in' => $studentCourse), true);
            $this->delete(array('id,in' => $remove), true);
		}
		return true;
	}
    
    // 取虚拟课程
    public function virtual($parent, $length)
    {
        if(!$parent || $length < 0) return false;
        $child = $this->get_child($parent, $length);       
        if(!$child) return false;
        $child = array_merge($parent, $child, array(
            'id' => $parent['id'] . "#" . $length,
            'pid' => $parent['id'],
            'lock' => 0,
            'rec_type' => '',
            'is_loop' => 0
        ));
        return $child;
    }
    
    // 生成子课程
    public function rec_create($pid, $length,$check_course = true,$push = false)
    {
        if(!$pid || $length < 0) return false;
        $child = $this->getRow(array('pid' => $pid, 'length' => $length));   
        $parent = $this->getRow(array('id' => $pid));         
        if(!$parent) return false;
        $_Event_grade = load_model('event_grade');
        if( empty($child) && $check_course === false)
        {
            $child = $this->virtual($parent, $length);
            if(empty($child)) return false;
            unset($child['id']);
            $id = $this->insert($child);
            if(!$id) return false;
            $child['id'] = $id;
        }else if($child){
        	if(!$check_course) return $child;
        }else{
			// throw new Exception("课程不存在！");
			return false;
		}
        $students = load_model('student_course')->getAll(array('event' => $parent['id']));
        $studentIds = array();
        foreach ($students as $item)
        {            
            $studentIds[] = $item['student'];
            $tmp = load_model('student_course')->getRow(array('event' => $child['id'], 'student' => $item['student'])); 
            if($tmp) continue;
            if($item['start_date'] != '0000-00-00 00:00:00' && strtotime($item['start_date']) > $length) continue;//还没开始
			if($item['end_date'] != '0000-00-00 00:00:00' && strtotime($item['end_date']) < ($length + $parent['length'])) continue; //（已经结束）开始时间结束变化过的不再生成！2013/9/5
            if(!load_model('student_course')->insert($child, $item['student'])) return false;
            
            // 创建班级关系
            if($child['grade'])
            {
                $res = $_Event_grade->insert(array(
                    'event' => $child['id'],
                    'grade' => $child['grade'],
                    'student' => $item['student']                        
                ));
                if(!$res) return false;
            }
        }
        $teachers = load_model('teacher_course')->getAll(array('event' => $parent['id']));       
        foreach ($teachers as $item)
        {
            $teacherIds[] = $item['teacher'];
            $child['priv'] = $item['priv'];
            $tmp = load_model('teacher_course')->getRow(array('event' => $child['id'], 'teacher' => $item['teacher']));
            if($tmp) continue;
            if(!load_model('teacher_course')->insert($child, $item['teacher'])) return false;
        }
        if($push){
        	$this->push($child,$teacherIds,$studentIds);
        }
        return $child;
    }
    
    public function childs($event)
    {
        import('repeat');
		$result = Repeat::resolve($event['start_date'], $event['end_date'], $event['rec_type'], $event['length']);
        return $result;
    }
    
    public function push($eventInfo,$teachers,$students){
    	//写logs
		$hash = md5($eventInfo['id']).rand(1000,9999);
		$logsData = array(
			'hash'=>$hash,
			'app'=>'event',
			'act'=>'add',
			'character'=>'teacher',
			'creator'=>$eventInfo['creator'],
			'target'=>array(),
			'ext'=>array(),
			'source'=>array(
				'event' => $eventInfo['id'],
				'is_loop' => 1,
				'whole' => 0
			),
			'data' => array(),
			'type'=>0,
		);
		if($teachers){
			logs('db')->add('event', $hash, array_merge($logsData,array('character'=>'teacher','target'=>$teachers)));
		}
		if($students){
			logs('db')->add('event', $hash, array_merge($logsData,array('character'=>'student','target'=>$students)));
		}
    }
    
    public function get_priv($uid, $event, $student=0, $charactor = 'teacher')
    {
        if(!$uid || !empty($event)) return false;
        if($uid == $event['creator']) return true;
        if($charactor == 'teacher')
        {
            $res = load_model('teacher_course')->getRow(array('event' => $event['id'], 'teacher' => $uid, 'status' =>0), false, 'priv');
            if(!$res || ($res['priv'] & 1) == false) return false;
        }else{            
            
        }
    }
}