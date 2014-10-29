<?php
/*
 * 课程
 * LYL
*/
class Event_Api extends Api
{

	public function __construct(){
		parent::_init();		
	}
    
    private $_eventFields = 'id, pid, text,course,start_date,end_date,class_time,`grade`,`type`, is_loop,`length`, rec_type, color, teacher,school,`status`,`lock`,modify_time,creator';
    
	public function add()
	{
		$text = Http::post('text', 'string', '');
		if(!$text) throw new Exception('标题不能为空');
		$course = Http::post('course', 'int', 0);
		if(!$course) throw new Exception('课程类型不能为空！');
		$student = Http::post('student', 'string', '');
		$start_date = Http::post('start_date', 'string', '');
		//if(!$start_date) throw new Exception('开始时间不能小于当前');
		$end_date = Http::post('end_date', 'string', '');
		if(!$end_date) throw new Exception('结束时间不正确');

		$length = intVal(strtotime($end_date) - strtotime($start_date));
		if($length < 1800) throw new Exception('课程时间不能小于30分钟！');
		if(date('Y-m-d', strtotime($start_date)) != date('Y-m-d', strtotime($end_date))) throw new Exception('课程时间不能跨天！');	

		$grade = Http::post('grade', 'int', 0);
		$rec_type = Http::post('rec_type', 'string', '');
		if($rec_type && count(explode("_", $rec_type)) < 5) throw new Exception("重复设置错误!@Er.event[rec_type]");

		$class_time = Http::post('class_time', 'string', '1.0');
		$color = Http::post('color', 'string', '');		
		$description = Http::post('description', 'string', '');
		$school = Http::post('school', 'int', 0);
		$character = Http::post('character', 'string', 'teacher');		
		$creator = $this->uid;
		$teacher = $character == 'teacher' ? $this->uid : 0;	
		$create_time = time();	
		$students = explode(",", $student);
		$lock = 0; // 锁定       
		// 学生验证          
		if($students)
		{            
            if($character == 'teacher')
            {
                foreach($students as $item)
                {	
                    $res = load_model('teacher_student')->getRow(array('teacher' => $this->uid, 'student' => $item), false, 'id');
                    if(!$res) throw new Exception("没有此学生!@Er.event.add.no.student[student:{$item}]");
                }
            }else{
                foreach($students as $item)
                {	
                    $res = load_model('user_student')->getRow(array('user' => $this->uid, 'student' => $item), false, 'id');
                    if(!$res) throw new Exception("没有此学生!@Er.event.add.no.student[student:{$item}]");
                }
            }
		}

		$is_loop = Http::post('is_loop', 'int', 0);		
		// 循环课程
		if($is_loop)
		{
			if(!$rec_type) throw new Exception('未设置循环属性！@Er.rec_type');
			$end = Http::post('end', 'string', '');
			if(!$end) throw new Exception('请设置课程截止时间！');            
            $end_time = date('H:i:s', strtotime($end_date));
			$_end_date = $end . " " . $end_time;
            if($end_date > $_end_date) throw new Exception("截止时间不能小于课程结束时间!@Er.event[end_date > end]");
            $end_date = $_end_date;
			$lock = 1;
		}else{
			$length = 0;
		}

		db()->begin();		
		try{
			$_Event = load_model('event');		
			$agent = Http::getSource();
			$data = compact('text', 'course', 'grade', 'start_date', 'end_date', 'rec_type', 'class_time', 'type', 'color', 'school', 'creator', 'length', 'teacher', 'create_time', 'is_loop', 'lock', 'agent');           
			$id = $_Event->insert($data);
			if(!$id) throw new Exception('添加失败！@Er.event.add');
			$event = $_Event->getRow($id, false, $this->_eventFields);
			// 增加老师的记录
			$res = load_model('teacher_course')->insert(array_merge($event, array('priv' => 15)), $this->uid);
			if(!$res) throw new Exception("课程生成失败!@Er.event.add.teacher[teacher:{$this->uid}]");
			// 学生的记录
			if($students)
			{
				$students = array_unique($students);
                foreach($students as $student)
                {
                    $res = load_model('student_course')->create($event, $student);
                    if(!$res) throw new Exception("课程生成失败!@Er.event.add[student:{$student}]");
                }
                $res = logs('db')->add('event', $this->_hash(Http::query()), $this->get_logs(array(
                    'act' => 'add',
                    'character' => 'student',											
                    'target' => $students,                   						
                    'source' => array('event' => $event['id'], 'is_loop' => $is_loop, 'whole' => $is_loop ? 1 : 0),
                    'type' => 2
                )));
			}
			db()->commit();			
			Out(1, '创建成功！', $_Event->Format($event));
		}catch(Exception $e)
		{
			db()->rollback();
			Out(0, $e->getMessage());
		}
	}
    
	public function update()
	{
		$id = Http::post('id');
		if(!$id) throw new Exception('参数错误！@Er.param.id');
		$_Event = load_model('event');		
		$hash = $this->_hash(Http::query());
		$is_loop = Http::post('is_loop', 'int', 0);
		$whole = Http::post('whole', 'int', 0);
        $character = Http::post('character', 'string', 'teacher');       
		db()->begin();		
		try
		{
			if(strpos($id, '#'))
			{
				list($pid, $length) = explode("#", $id);				
                $event = $_Event->rec_create($pid, $length);               
			}else{
				$event = $_Event->getRow($id);
			}			
			if(empty($event)) throw new Exception("课程不存在！@Er.event.un_exists[id:$id]");
			
			if($is_loop && !empty($event['lock'])) throw new Exception("该课程正在处理中，请稍后！@Er.event[....]");
            
             //             
            if($character != 'teacher')
            {
                if($event['creator'] != $this->uid) throw new Exception("没有权限！@Er.event.parent.promise");
            }else{
                $teacherRes = load_model('teacher_course')->getRow(array('event' => $event['id'], 'teacher' => $this->uid)); // 权限
                if(!$teacherRes || ($event['creator'] != $this->uid && !($teacherRes['priv'] & 1))) throw new Exception("没有权限！@Er.event.teacher.promise");
            }
			
			if(!empty($event['school'])) throw new Exception("没有权限，机构课程不允许修改！@Er.event.school.promise"); // 机构课程暂时不允许修改

			// 权限
			// $priv = load_model('teacher_course')->getColumn(array('event' => $event['id'], 'teacher' => $this->uid), 'priv');			
			// if(empty($priv[0]) || !($priv[0] & 1)) throw new Exception('无权限！@Er.without permission');
			
			// 参数
			$param = Http::query();		
			$colunms = array(
				'text', 'color', 'course', 'class_time', 'start_date', 'end_date', 'grade', 'rec_type', 'end'
			);		
			$data = array();
			
			if(isset($param['text']))
			{
				if($param['text'] == '') throw new Exception('标题不能为空！@Er.text null');
				$event['text'] == $param['text'] || $data['text'] = $param['text'];
			}
			if(isset($param['color']) && $event['color'] != $param['color'])
			{
				$data['color'] = $param['color'];
			}
			if(isset($param['grade']) && $event['grade'] != $param['grade'])
			{
				$data['grade'] = $param['grade'];
			}
			
			if(isset($param['course']))
			{
				if($param['course'] == '') throw new Exception('课程类型不能为空@Er.param.course');
				$event['course'] == $param['course'] || $data['course'] = $param['course'];
			}

			if(isset($param['class_time']))
			{				
				$event['class_time'] == $param['class_time'] || $data['class_time'] = $param['class_time'];
			}

			// 时间
			if(isset($param['start_date']) && !$param['start_date']) throw new Exception('课程开始时间错误！@Er.param[start_date]');
			if(isset($param['end_date']) && !$param['end_date']) throw new Exception('课程结束时间错误！@Er.param[end_date]');
            
			if(isset($param['start_date']) && isset($param['end_date']))
			{
				$length = intVal(strtotime($param['end_date']) - strtotime($param['start_date']));
				if($length < 1800) throw new Exception('课程时间不能小于30分钟！');				
				if(date('Y-m-d', strtotime($param['end_date'])) != date('Y-m-d', strtotime($param['start_date']))) throw new Exception('课程时间不能跨天！');
                
				strtotime($param['start_date']) == strtotime($event['start_date']) || $data['start_date'] = $param['start_date'];
				strtotime($param['end_date']) == strtotime($event['end_date']) || $data['end_date'] = $param['end_date'];				
                
			}
			if(strpos($id, '#') && (!empty($param['rec_type']) || $is_loop)) // 禁止循环课程子课程修改为循环课
			{
				throw new Exception("禁止的操作!@Er.event[Forbidden]");	
			}

			if(!empty($param['rec_type']) && count(explode("_", $param['rec_type'])) < 5) throw new Exception("重复设置错误!@Er.event[rec_type]");
			// substr_count($param['rec_type'], '_') < 5
            
			// 学生		
			if(isset($param['student']) && $students = explode(",", $param['student']))
			{
                $students = array_unique($students);
				foreach($students as $item)
				{	
					$res = load_model('teacher_student')->getRow(array('teacher' => $this->uid, 'student' => $item), false, 'id');
					if(!$res) throw new Exception("没有此学生!@Er.event.update.no.student[student:{$item}]");					
				}								
			}           
			$push = $is_loop==0 && (isset($data['start_date']) || isset($data['end_date'])) ? 2 : 0;			
			// 循环处理
			if($is_loop)
			{				
				if(!isset($param['start_date'], $param['end_date'])) throw new Exception("请设置课程时间!@Er.event.date");
				if(!isset($param['end'])) throw new Exception("请设置系列课程截止日期!@Er.event.end");
				if(!isset($param['rec_type'])) throw new Exception("请设置重复频率!@Er.even.rec_tpye");		
				//unset($data['start_date']); // 开始时间不能修改

				$data['rec_type'] = $param['rec_type'];               
				$end_time = datetime('H:i:s', strtotime($param['end_date']));
                $data['end_date'] = date('Y-m-d', strtotime($param['end'])) .' '. $end_time;
                if($param['end_date'] > $data['end_date']) throw new Exception("截止时间不能小于课程结束时间!@Er.event[end_date > end]");
                if(!$whole && strtotime($param['start_date']) < time()) throw new Exception("开始时间不能是过去时间！@Er.param.start_date");				
				$data['length'] = $length;
				$data['is_loop'] = 1;

				if($event['is_loop'] == 0 ||
					$event['rec_type'] != $data['rec_type'] ||
					(isset($param['start_date']) && strtotime($event['start_date']) != strtotime($param['start_date'])) || 
					strtotime($event['end_date']) != strtotime($data['end_date'])					
				)
				{
					$push = 2;					
				}
				if(!$_Event->rec_clear($event['id'], $whole)) throw new Exception('课程处理失败！'); // 循环变更时处理
				$data['lock'] = 1;
			}else{
				if($event['is_loop']) // 循环改非循环
				{
					if(!$_Event->rec_clear($event['id'], $whole)) throw new Exception('课程处理失败！'); // 循环变更时处理
					$data['rec_type'] = '';
					$data['length'] = 0;
					$data['is_loop'] = 0;
					$data['start_date'] = isset($data['start_date']) ? $data['start_date'] : $event['start_date'];
					if(!isset($data['end_date']))
					{						
						$data['end_date'] = date('Y-m-d H:i:s', strtotime($data['start_date']) + $event['length']);
					}
                    $push = 2;
				}
			}
			if($data) 
			{              
				$res = $_Event->update($data, $event['id']);            
				if(!$res) throw new Exception('更新失败!@Er.update');                
				$teacherEvent = array();
				isset($data['text']) && $teacherEvent['remark'] = $data['text'];
				isset($data['color']) && $teacherEvent['color'] = $data['color'];
				// 老师
				if($teacherEvent)
				{	
					$res = load_model('teacher_course')->update($teacherEvent, array('event' => $event['id']));
					if(!$res) throw new Exception('课程更新失败！@Er.event.update[teacher]');					
					$resource = load_model('teacher_course')->getAll(array('event' => $event['id']));                    
                    foreach($resource as $item)
                    {
                        if($item['teacher'] == $this->uid) continue; // 自己不发
                        if( !($item['priv'] & 1) && !($item['priv'] & 4)) continue; // 1 上课，2教务，4点评
                        $res = logs('db')->add('event', $hash, $this->get_logs(array(
                            'character' => 'teacher',				
                            'hash' => $hash,									
                            'source' => array('event' => $event['id'],  'is_loop' => $is_loop, 'whole' => $whole,
                                'old'=>array(
                                    'remark' => $item['remark'],  'school' => $event['school'],
									'pid' => $event['pid'], 'length' => $event['length'], 'is_loop' => $event['is_loop'], 'rec_type' => $event['rec_type'],
                                    'start_date' => $event['start_date'], 'end_date' => $event['end_date'],
                                ),
                            ),
                            'type' => $push
                        )));
                        if(!$res) throw new Exception("课程更新失败！@Er.event.update[teacher:{$item['teacher']}]");
                    }					
				}				
			}	
			
            $data && $event = array_merge($event, $data);
			if($whole || $event['is_loop'] == 0)
			{
				$students = load_model('student_course')->compare($event['id'], $students, false);// 比较整个 whole
			}else{
				$students = load_model('student_course')->compare($event['id'], $students, true);// 比较当前
			}
			
			if($students['new'])
			{
				if($whole || $event['is_loop'] == 0)                  
				{
					foreach($students['new'] as $item)
					{                        
						$res = load_model('student_course')->create($event, $item); //生成学生-课程关系 
						if(!$res) throw new Exception("课程生成失败!@Er.event.add[student:{$item}]");                    
					}  
				}else // 循环课程之后课程
				{
					// 将要发生的课程
					$recent = $_Event->recent($event, 'right'); //最近课程的下一节
					if($recent)
					{
						foreach($students['new'] as $item)
						{                        
							$res = load_model('student_course')->create(array_merge($event, array('start_date' => $recent['start_date'])), $item); //生成学生-课程关系 
							if(!$res) throw new Exception("课程生成失败!@Er.event.add[student:{$item}]");                    
						}
					}
				}                 
				$res = logs('db')->add('event', $hash, $this->get_logs(array(
					'act' => 'add',
					'character' => 'student', 
					'target' => $students['new'],
					'hash' => $hash,
					'source' => array('event' => $event['id'],'is_loop' => $is_loop, 'whole' => $whole),
					'type' => 2
				)));
				if(!$res) throw new Exception("课程更新失败！@Er.event.new.student"); 
			}			
			// 删除学生
			if($students['lost'])
			{
				foreach($students['lost'] as $item)
				{
					$resource = load_model('student_course')->getRow(array('event' => $event['id'], 'student' => $item), false, 'remark');
					$logs = $this->get_logs(array(
						'character' => 'student',
						'act' => 'delete',
						'hash' => $hash,
						'target' => $item,
						'source' => array('event' => $event['id'], 'is_loop' => $is_loop, 'whole' => $whole, 
							'old'=>array(
								'remark' => $resource['remark'], 'school' => $event['school'], 'teacher' => $event['creator'],
								'pid' => $event['pid'], 'length' => $event['length'], 'is_loop' => $event['is_loop'], 'rec_type' => $event['rec_type'],
								'start_date' => $event['start_date'], 'end_date' => $event['end_date']
					   )),
						'type' => 2
					));
					$res = logs('db')->add('event', $hash, $logs);
					if(!$res) throw new Exception("课程更新失败！@Er.event.lost.student"); 
				}
				
				if($whole || $event['is_loop'] == 0)
				{
					load_model('student_course')->delete(array('event' => $event['id'], 'student,in' => $students['lost']), true);// 子课程 rec_delete已删除
				}else{						
					$happened = $_Event->recent($event); // 已经发生的最后一节
					if($happened){	// 修改学生-课程关系
						load_model('student_course')->update(array(								
							'end_date' => $happened['end_date']
						), array(
							'event'=> $event['id'], 
							'student,in' => $students['lost']
						));
					}else{ // 此课程下没有已上过的课程
						load_model('student_course')->delete(array('event'=> $event['id'], 'student,in' => $students['lost']), true); //生成学生-课程关系
					}
				}
				// 删除课程、学生、班级关系
				if($event['grade'])
				{
					load_model('event_grade', array('table' => 'event_grade'))->delete(array(
						'event' => $event['id'], 
						'grade' => $event['grade'], 
						'student,in' => $students['lost']
					), true);
				}
				// load_model('student_course')->delete(array('event'=> $event['id'], 'student,in' => $students['lost']), true); // 去除此学生与循环课程的关系 2013/9/30
			}
                
			// 学生推送
			if($students['keep'] && $push)
			{
				foreach($students['keep'] as $item)
				{
					$resource = load_model('student_course')->getRow(array('event' => $event['id'], 'student' => $item), false, 'remark');
					$logs = $this->get_logs(array(
						'character' => 'student',				
						'hash' => $hash,
						'target' => $item,
						'source' => array('event' => $event['id'], 'is_loop' => $is_loop, 'whole' => $whole, 
							'old'=>array(
								'text' => $resource['remark'], 'school' => $event['school'], 'teacher' => $event['creator'],
								'pid' => $event['pid'], 'length' => $event['length'], 'is_loop' => $event['is_loop'], 'rec_type' => $event['rec_type'],
								'start_date' => $event['start_date'], 'end_date' => $event['end_date']
					   )),
					   'type' => $push
					));
					$res = logs('db')->add('event', $hash, $logs);
					if(!$res) throw new Exception("课程更新失败！@Er.event.lost.student");
				}
			}

			//更新记录
			if($students['keep'] && $event['is_loop'])
			{
				if($whole)
				{
					load_model('student_course')->update(array('start_date' => '', 'end_date' => ''), array(
						'event' => $event['id'], 
						'student,in' => $students['keep']
					));
				}
			}
			db()->commit();
			Out(1, '修改成功！', $_Event->getRow($event['id'], true, $this->_eventFields));
		}catch(Exception $e){
			db()->rollback();
			Out(0, $e->getMessage());
		}
	}
    
	public function delete()
	{     
		$id = Http::post('id');
		if(!$id) throw new Exception('参数错误！@Err[param.id]');
		$whole = Http::post('whole', 'int', 0); // 如果是循环课程默认删除今天之后的课程
		$_Event = load_model('event');		
		$hash = $this->_hash(Http::query());
        $character = Http::post('character', 'string', 'teacher');
		db()->begin();
		try
		{
			$childs = Array();
			if(strpos($id, '#'))
			{
				list($pid, $length) = explode("#", $id);	
				$event = $_Event->getRow(array('pid' => $pid, 'length' => $length), false , $this->_eventFields . ",attend,`leave`,absence,attended,commented");
				if(!$event)
					$event = $_Event->rec_create($pid, $length);
			}else{				
				$event = $_Event->getRow($id);			
				if(!$event)
				{
					// 父课程已经删除
					$childs = $_Event->getColumn(array('pid' => $id, 'status' => 0), 'id');
					if($childs)
					{
						$_Event->delete(array('id,in' => $childs), true); // 删除所有子课程
						load_model('teacher_course')->delete(array('event,in' => $childs), true); // 删除所有子课程
						load_model('student_course')->delete(array('event,in' => $childs), true); // 删除所有子课程
						db()->commit();
						Out(1, '成功！');
					}
				}
			}            
			if(empty($event) || $event['status'] == 1 || ($event['pid'] && $event['rec_type'] == 'none')) throw new Exception("课程不存在或已删除！@Er.event.un_exists[id:$id]");
            if($event['lock'] == 1) throw new Exception("该课程正在处理中，请稍后！@Er.event[....]");
            
             // 家长删除，老师删除            
            if($character != 'teacher')
            {
                if($event['creator'] != $this->uid) throw new Exception("没有权限！@Er.event.parent.promise");
            }else{
                $teacherRes = load_model('teacher_course')->getRow(array('event' => $event['id'], 'teacher' => $this->uid)); // 权限                
                if(!$teacherRes || ($event['creator'] != $this->uid && !($teacherRes['priv'] & 1))) throw new Exception("没有权限！@Er.event.teacher.promise");
            }
            
            $students = array_unique(load_model('student_course')->getAll(array('event' => $event['id']), '', '', false, false, 'id,student,remark'));			
            $teachers = array_unique(load_model('teacher_course')->getAll(array('event' => $event['id']), '', '', false, false, 'id,teacher,remark'));
            
			if($event['is_loop'])
            {
                if(!$_Event->rec_clear($event['id'], $whole))     throw new Exception("子课程删除失败！@Er.event[delete:{$event['id']}]");
            }

            if($event['is_loop'] && $whole ==0) // 
            {
				$time = time();
                $recent = $_Event->recent($event); // 最后一节已上课程

                if(!empty($recent['end_date']) && strtotime($recent['end_date']) < strtotime($event['end_date']))
                {
                   	$_Event->update(array('end_date' => $recent['end_date']), $event['id']);
                    load_model('student_course')->update(array('end_date' =>$recent['end_date']), array(
                        'event' => $event['id'], 
                        'end_date,>' => $recent['end_date'],
                        'student,in' => $students
                    ));
                    ///load_model('teacher_course')->delete(array('event' => $event['id'], 'teacher,in' => $teachers));
                }else // 已经没有已上课程 此循环课程全清除
				{
					load_model('teacher_course')->delete(array('event' => $event['id']), true);
					load_model('student_course')->delete(array('event' => $event['id']), true);
					load_model('event')->delete($event['id'], true);
				}
            }else
            {               
                if($event['pid']) // 子课程
                {                   
					$_Event->update(array('rec_type' => 'none'), $event['id']);                    
                }else {                    
                    $_Event->delete($event['id'], true);                    
                }     
            }
			
            if($teachers)
            {    
                foreach($teachers as $key => $item)
                {
					($event['pid'] !=0 || ($event['is_loop'] ==1 && !$whole)) || load_model('teacher_course')->delete($item['id'], true); // 1、非子课程， 2循环课程且whole=1

                    if($item == $this->uid) continue;
                    $res = logs('db')->add('event', $hash, $m = $this->get_logs(array(
                        'character' => 'teacher',
                        'target' => $item['teacher'],
                        'hash' => $hash,																					
                        'source' => array('event' => $event['id'], 'is_loop' => $event['is_loop'], 'whole' => $whole,							
							'old'=>array(
								'remark' => $item['remark'],  'school' => $event['school'], 'teacher' => $event['creator'],
								'pid' => $event['pid'], 'length' => $event['length'], 'is_loop' => $event['is_loop'], 'rec_type' => $event['rec_type'],
								'start_date' => $event['start_date'], 'end_date' => $event['end_date'],
							),                            
						),
                        'type' => 1                        
                    )));
                    if(!$res) throw new Exception("删除失败!@Er.event.logs.teacher[{$teacher}]");
					
                }
            }
            // 推送
            if($students)
            {         
				foreach($students as $key=>$item)
				{					
					($event['pid'] !=0 || ($event['is_loop'] ==1 && !$whole)) || load_model('student_course')->delete($item['id'], true); // 子课程
					$res = logs('db')->add('event', $hash, $t = $this->get_logs(array(
						'character' => 'student',
						'target' => $item['student'],
						'hash' => $hash,																					
						'source' => array('event' => $event['id'], 'is_loop' => $event['is_loop'], 'whole' => $whole,
							'old'=>array(
								'remark' => $item['remark'],  'school' => $event['school'] , 'teacher' => $event['creator'],
								'pid' => $event['pid'], 'length' => $event['length'], 'is_loop' => $event['is_loop'], 'rec_type' => $event['rec_type'],
								'start_date' => $event['start_date'], 'end_date' => $event['end_date'],
							),
						),
						'type' => 1
					)));					
					if(!$res) throw new Exception("课程删除失败！@Er.event.logs[student]");
				}            
			}
			db()->commit();
			Out(1, '成功！', array('id' => $event['id']));
		}catch(Exception $e)
		{
			db()->rollback();
			Out(0, $e->getMessage());
		}
	}
	
	
	// 备注
	public function remark()
	{				
        $character = Http::post('character', 'string', 'student');
		$student = Http::post('student', 'int', 0);
		$id = Http::post('id', 'string', '');
		$_Event = load_model('event');
		$param = Http::query();
		$data = array();		
        $whole = Http::post('whole', 'int', 0);
		$columns = array('remark', 'color', 'fee');
		foreach($param as $key => $value)
		{
			in_array($key, $columns) && $value && $data[$key] = $value;
		}
		foreach($data as $key => $item){
			switch ($key)
			{
				case 'color':
					//if(strpos($item, "#") !==0 || strlen($item) != 7) // #FF0000
					//throw new Exception('参数错误!@Er.param[color:{$item}]');
				break;
				case 'fee':
					if(!is_numeric($item))
						throw new Exception('参数错误!@Er.param[fee:{$item}]');
				break;
				default :
				break;
			}
		}
		if(empty($data)) throw new Exception('未改变!');
		db()->begin();
		try
		{			
			if(strpos($id, '#'))
			{
				list($pid, $length) = explode("#", $id);
				$event = $_Event->rec_create($pid, $length, true);
			}else{
				$event = $_Event->getRow($id);				
			}			
			if(empty($event)) throw new Exception('课程不存在!');   
            $events = array();
            // 循环课程的备注
            if($event['is_loop'])
            {
                $where = array('pid' => $event['id']);
                $whole || $where['start_date,>'] = date('Y-m-d H:i:s');
                $events = $_Event->getColumn($where, 'id');                            
            }
            $events[] = $event['id'];            
            if(!$student)
            {                    
                load_model('teacher_course')->update($data, array('event,in' => $events, 'teacher' => $this->uid)); 
            }else
            {
                load_model('student_course')->update($data, array('event,in' => $events, 'student' => $student));  
            }            
			db()->commit();
			Out(1, '成功！');
		}catch(Exception $e)
		{
			db()->rollback();
			Out(1, $e->getMessage());			
		}
	}

	// 获取课程详细
	public function info()
	{	
		$id = Http::post('id', 'string', '');		
		if($id == '') throw new Exception("课程不存在或已被删除！@Er.event.info[id:$id]");		
		$_Event = load_model('event');
		if(strpos($id, '#'))
		{
			list($pid, $length) = explode("#", $id);
			$event = $_Event->getRow(array('pid' => $pid, 'length' => $length), false , $this->_eventFields . ",attend,`leave`,absence,attended,commented");
			if(!$event)
			{
				$parent = $_Event->getRow(array('id' => $pid), false, $this->_eventFields . ",attend,`leave`,absence,attended,commented");           
				$event = $_Event->virtual($parent, $length); 
			}           
		}else{
			$event = $_Event->getRow($id, false, $this->_eventFields . ",attend,`leave`,absence,attended,commented");
		}		
		if(empty($event)) throw new Exception('课程不存在!');
		$type = Http::post('type', 'int', 0);
		$student = Http::post('student', 'int', 0);
		if($type ==1 && !$student) throw new Exception("未指定学生！@Er.event.info[student]");		
		// $event = $_Event->Format($event);
		$event['course'] = $re = load_model('course')->getRow($event['course'], true, 'id,title,`type`,teacher,school,experience,fee');	
		$event['grade'] = load_model('grade')->getRow($event['grade'], true, 'id, name');
        $event['school'] && $event['school']= load_model('school')->getRow($event['school'], true, 'id,name,pid');
        $event['teacher'] && $event['teacher']= load_model('user')->getRow($event['teacher'], true, 'id,firstname,lastname,nickname,avatar'); 
        
		$students = array();		
		//家长
		if($type == 1){
			$_Handle = load_model('student_course');
			$where = array('event' => $id, 'student' => $student, 'status' => 0);
			$relation = $_Handle->getRow($where, true, 'remark,color,fee,student,attend,`leave`,absence,commented');
			if(!$relation) throw new Exception("没有此课程！@Er.event.student[{$student}]");	
			$event = array_merge($event, $relation);			
			$comment = load_model('comment')->getRow(array('event' => $event['id'], 'student' => $student), true, '*', '`create_time` Desc');	
			$notify = load_model('notify')->getRow(array('event' => $event['id'], 'student,like' => '"' . $student . '"'), true,'*', 'create_time desc');
		//老师
		}else{
			$_Handle = load_model('teacher_course');
			$where = array('event' => $id, 'teacher' => $this->uid, 'status' => 0);
			$relation = $_Handle->getRow($where, true, "teacher as 'user',remark,color,priv");
			
			$event['teacher'] = load_model('user')->getRow($this->uid, true, 'id,firstname,lastname,nickname,avatar');

			if(!$relation) throw new Exception("没有此课程！@Er.event.teacher[{$this->uid}]");	
			$event = array_merge($event, $relation);
			// 父课程			
			if(strpos($event['id'], '#'))
			{				
				$comment = array();
				$studentEvent = load_model('student_course')->getAll(array('event' => $event['pid']), '', '', false, true, 'fee,student,attend,`leave`,absence,commented');				
			}else{
				$commentWhere = array('event' => $event['id']);
				$student && $commentWhere['student'] = $student;
				$comment = load_model('comment')->getRow($commentWhere, true, '*', '`create_time` Desc');			
				$studentEvent = load_model('student_course')->getAll(array('event' => $event['id']), '', '', false, true, 'fee,student,attend,`leave`,absence,commented');			
			}
			foreach($studentEvent as $item)
			{
				$res = load_model('student')->getRow($item['student'], true, 'avatar,name,nickname');
				$students[] = array_merge($item, $res);
				$event['students'][] = $item['student'];
			}			
			$notify = load_model('notify')->getRow(array('event' => $event['id']), true,'*', 'create_time desc');
		}       
        
        if($notify){
			$notify['creator'] = load_model('user')->getRow($notify['creator'],false,'id,nickname,firstname,lastname,hulaid,avatar');
			$notify['student'] = json_decode($notify['student'],true);
			$notify['teacher'] = json_decode($notify['teacher'],true);
			$notify['attachs'] = json_decode($notify['attachs'],true);
			$notify['create_time'] = date('Y-m-d H:i:s',$notify['create_time']);  
		}
		$event = $_Event->Format($event);
		Out(1, '', compact('event', 'comment','notify', 'students'));
	}

	public function getList()
	{		
		$student = Http::post('student', 'int', 0);
		$type = Http::post('type', 'int', 0);
		if($type == 1 && !$student) throw new Exception("未指定学生！@Er.event.info[student]");		
		$start_date = Http::post('start_date', 'string', '');
		$end_date = Http::post('end_date', 'string', '');
		$pid = Http::post('pid', 'int', 0);
		$school = Http::post('school', 'int', 0); // 机构	
		$attend = Http::post('attend', 'int', 0); // 出勤
		$leave = Http::post('leave', 'int', 0); // 请假
		$absence = Http::post('absence', 'int', 0); // 缺勤	
        $whole = 
		$ala = $attend + $leave + $absence;
		if($ala > 1){
			Out(0, '参数错误,attend,leave,absence 只能传1个');
		}        
		$comment = Http::post('comment', 'int', 0); // 已点评 待点评
		$teacher = Http::post('teacher', 'int', 0); // 查看某个老师的课程	
		// $start_date || $start_date = date("Y-m-d", mktime(0,0,0, date('m'), 1, date('Y')));
		// $end_date || $end_date = date("Y-m-d", mktime(0,0,0, date('m')+1, 0, date('Y')));		
		if($type == 1) // 学生
		{
			if(!$student) throw new Exception('未指定学生！');
			$res = load_model('user_student')->getRow(array('user'=> $this->uid, 'student'=> $student)); // 权限
			if(!$res) throw new Exception('无权限');
			$_Handle = load_model('student_course');
			$where = compact('start_date', 'end_date', 'student', 'attend','leave','absence','ala','school', 'comment', 'teacher', 'pid');	
			$user = $student;            
			$result = $pid && $whole ? $_Handle->rec_all($pid, $user, true) : $_Handle->getList($where, true);
		}else if($type == 2){
			$teacher = $this->uid;
			$_Handle = load_model('teacher_course');
			$where = compact('start_date', 'end_date', 'attend','leave','absence','ala', 'comment',  'school', 'teacher', 'pid');            
			$result = $pid && $whole ? $_Handle->rec_all($pid, $teacher, true) : $_Handle->getList($where, true);            
		}else{
			$result['teacher']  = load_model('teacher_course')->getList(array('teacher' => $this->uid), true);
			$students = load_model('user_student')->getColumn(array('user' => $this->uid), 'student');
			$result['student']  = $students ? load_model('student_course')->getList(array('student' => $students, 'ala' => 0), true) : array();			
		}
        
		if(empty($result))  throw new Exception('无数据！');
		Out(1, '', $result);
	}
    
    public function sub()
    {
        $pid = Http::post('pid', 'int', 0);
        $whole = Http::post('whole', 'int', 0);
        $character = Http::post('character', 'string', 'teacher');
        $student = Http::post('student', 'int', 0);
        if($character == 'teacher')
        {
            $_Handle = load_model('teacher_course');
            $user = $this->uid;
        }else{            
            if(!$student)  throw new Exception('未指定学生！');
            $user = $student;
            $_Handle = load_model('student_course');
        }
        $result['parent'] = load_model('event')->getRow($pid);
        if($whole)
        {            
            $result['childs'] = $_Handle->rec_all($pid, $user, true);
        }else{
            $result['childs'] = $_Handle->getList(array('pid' => $pid, 'teacher' => $user), true);
        }
        Out(1, '', $result);
        
         
        if($whole == 0)
        {
            return $this->getList(array('pid' => $pid, 'student' => $student));
        }
    }
    
	public function getSub()
	{
		$id = Http::post('id', 'int', 0);
		$event = load_model('event')->getRow($id);
		if($event['pid'] == 0)
		{
			import('repeat');
			$result = Repeat::resolve($event['start_date'], $event['end_date'], $event['rec_type'], $event['length']);
			out(1, '', $result);
		}
		out(1, '', $event);		
	}
    
    public function child()
    {        
        $id = Http::post('id', 'int', 0);
        $length = Http::post('length', 'string', '');
        if(!$id || !$length) Out(0, '生成失败');        			
        $event = load_model('event')->rec_create($id, $length);
        Out(1, $event);
    }
    
	private $_logs = array(
		'hash' => '', 
		'app' => 'event', 
		'act' => 'add', 
		'character'=> 'teacher',
		'creator' => '', 
		'target' => '', 
		'ext' => array(),
		'source' => array(), 
		'data' => array()
	);	
	
	private function get_logs(array $param)
	{
		isset($param['hash']) || $this->_logs['hash'] = $this->_hash(Http::query());
		isset($param['target']) || $this->_logs['target'] = $this->uid;
		isset($param['app']) || $this->_logs['app'] = $this->app;		
		isset($param['act']) || $this->_logs['act'] = $this->act;
		$this->_logs['creator'] = $this->uid;
		return array_merge($this->_logs, $param);		
	}

	private  function _hash(array $param)
	{
		if(empty($param)) return '';
		// $param = Http::post();		
		extract($param);
		$hashStr = join("", array_values($param));		
		$hash = md5($hashStr); // 标识
		if(redis()->hget('event-trad', $hash))
		{
			// throw new Exception("课程已经在处理中！");
		}else{
			redis()->hset('event-trad', $hash, $param, 0);
		}
		return $hash;
	}

}