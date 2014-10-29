<?php
/**
 * msgtype
 * SSdesc  考勤
 * SSend
 */
class Attendance_Api extends Api
{	
	public function __construct(){
		parent::_init();
	}

	/**
	 * structdef.xml
	 * 	SSaction     update
	 *  SSdesc       考勤 
	 *  SSpargam 
	 * 		character varchar  角色(默认teacher)
	 * 		student   int   学生id
	 * 		event  int   课程id
	 * 		attend	int   出勤
	 * 		absence	int   请假 
	 * 		leave	int   缺勤
	 *  SSreturn 
	 * SSend
	 */
	public function update()
	{
		$character = Http::post('character', 'string', 'teacher');
		$event = Http::post('event', 'int', 0);
		$student = Http::post('student', 'int', 0);	
		$attend = Http::post('attend', 'string', ''); // 出勤
		$leave = Http::post('leave', 'string', ''); // 请假
		$absence = Http::post('absence', 'string', ''); // 缺勤	
        /*
		$ala = $attend + $leave + $absence;
		if($ala > 1){
			Out(0, '参数错误,attend,leave,absence 只能传1个');
		}
        */
        if(!$event) throw new Exception('参数错误！@Er.param.event');
        if(!$leave && !$attend && !$absence) Out(1, '成功'); // 没有变化 
            
		db()->begin();
		try{	
			//课程信息
			if(strpos($event, '#')){
				list($pid, $length) = explode("#", $event);				
				$eventResource = load_model('event')->rec_create($pid, $length);
			}else{
				$eventResource = load_model('event')->getRow($event);				
			}
			if(!$eventResource) throw new Exception('此课程已经被删除！');
			$event = $eventResource['id'];
            
			//学生是否有该课程            
			//$studentCourse = load_model('student_course')->getRow(array('event' => $event, 'student' => $student, 'status' => 0));		
			//if(!$studentCourse)	throw new Exception('该学生没有此课程！');
            
			//课程是否已经开始或结束
			$open = 0;
			if($eventResource['start_date'] < date('Y-m-d H:i:s')){
				$open = 1;
			}
            
			if($character == "teacher"){
				//老师是否有考勤权限
				$teacherCourse = load_model('teacher_course')->getRow(array('event' => $event, 'teacher' => $this->uid, 'status' => 0), 'priv');			
				if(empty($teacherCourse) || $teacherCourse['priv'] & 2 == false) throw new Exception('您没有考勤权限！');
                $attend = $attend ? explode(",", $attend) : array();
                $absence = $absence ? explode(",", $absence) : array();
                $leave = $leave ? explode(",", $leave) : array();                
                $update = array('attend' => 0, 'absence' => 0, 'leave' => 0);               
                if(!empty($attend))
                {                    
                    load_model('student_course')->update(array_merge($update, array('attend' => 1,'attended' => 1)), array('student,in' => $attend, 'event' => $event));
                }                
                if(!empty($leave))
                {                    
                    load_model('student_course')->update(array_merge($update, array('leave' => 1,'attended' => 1)), array('student,in' => $leave, 'event' => $event));
                }                
                if(!empty($absence))
                {                    
                    $res = load_model('student_course')->update(array_merge($update, array('absence' => 1,'attended' => 1)), array('student,in' => $absence, 'event' => $event));                    
                }				
				//更新课程考勤状态
				if(!$eventResource['attended']){
					if(!load_model('event')->update(array('attended' => 1), array('id' => $event))) throw new Exception('考勤失败！');	
				}			
				db()->commit();                
				Out(1, '考勤成功');
			}
            /*
            else{
				if($absence || $attend)   throw new Exception('学生只能请假！');	
				if($leave){
					//如果原先不是请假
					if(!$studentCourse['leave']){
						//请假+1
						if(!load_model('event')->increment('leave', array('id' => $event))) throw new Exception('考勤失败！');
						//如果原先是缺勤
						if($studentCourse['absence']){
							//缺勤-1
							if(!load_model('event')->decrement('absence', array('id' => $event))) throw new Exception('考勤失败！');			
						//如果原先是出勤
						}else{
							//出勤-1
							if(!load_model('event')->decrement('attend', array('id' => $event))) throw new Exception('考勤失败！');	
						}
					}	
				}
				db()->commit();
				Out(1, '成功', load_model('event')->Format($eventResource));
			}
             * 
             */
		}catch(Exception $e)
		{
			db()->rollback();
			Out(0, $e->getMessage());
		}
	}
}
