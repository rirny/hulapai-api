<?php
/**
 * msgtype
 * SSdesc  机构
 * SSend
 */
class School_Api extends Api
{
	
	public function __construct(){
		parent::_init();
	}


	public function index(){
		
	}

	/**
	 * 单机构信息
	 */
	public function info()
	{
		$id = Http::post('id', 'int', 0);
		$name = Http::post('name', 'trim', '');
		if(!$id && !$name) throw new Exception('参数错误！');
		$_School = load_model('school');
		if($id){
			$schoolInfo = $_School->getRow($id,false,'*');
		}else{
			$schoolInfo = $_School->getRow("code = '$name' or name = '$name'",false,'*');
		}
		$schoolInfo['comments'] = load_model('comment')->getRow("pid=0 And `event`=0 And school='$id' And ((teacher=0 And `character`='student') Or (`student`=0 And `character`='teacher')) ", false, '*', 'create_time Desc');
		out(1, '',array('school'=>$schoolInfo));
	}
	
	/**
	 * 机构列表
	 */
	public function getList()
	{
		$character = Http::post('character', 'trim', '');
		if(!in_array($character,array('teacher','student'))) throw new Exception('参数character错误！');
		$student = Http::post('student', 'int', 0);
		$teacher = Http::post('teacher', 'int', 0);
		if($character =="teacher"){
			if(!$teacher) throw new Exception('teacher不能为空！');
			$schools = load_model('school_teacher')->getColumn("teacher = $teacher",'school');
		}elseif($character =="student"){
			if(!$student) throw new Exception('student不能为空！');
			$schools = load_model('school_student')->getColumn("student = $student",'school');
		}
		if(!$schools) throw new Exception('没有机构！');
		$schoolIds = implode(',',$schools);
		$schools = load_model('school')->getAll("id in ($schoolIds)", '', '', false, false, '*');
		out(1, '',array('schools'=>$schools));
	}
	
	/**
	 * 机构老师有关系的学生
	 */
	public function teacher_student()
	{
		$school = Http::post('id', 'int', 0);
		$teacher = Http::post('teacher', 'int', 0);
		if(!$school || !$teacher) throw new Exception('参数错误！');
		if(!load_model('school')->getRow($school)) throw new Exception('机构不存在！');
		if(!load_model('school_teacher')->getRow(array('school'=>$school,'teacher'=>$teacher))) throw new Exception('该老师和机构不存在关系！');
		$StudentIds = load_model('school_teacher')->getTeacherStudentIds($school,$teacher,true);
		$studentInfos = load_model('student')->getAll("id in ($StudentIds)", true);
		if($studentInfos){
			foreach($studentInfos as &$studentInfo){
				$studentInfo['parent'] = load_model('user_student')->get_parents($studentInfo['id'], true);
			}
		}
		out(1, '',array('students'=>$studentInfos));
	}	
	
	
	/**
	 * 机构老师脱离机构
	 */
	public function leave()
	{
		$school = Http::post('id', 'int', 0);
		$teacher = Http::post('teacher', 'int', 0);
		if(!$school || !$teacher) throw new Exception('参数错误！');
		if(!load_model('school')->getRow($school)) throw new Exception('机构不存在！');
		if(!load_model('school_teacher')->getRow(array('school'=>$school,'teacher'=>$teacher))) throw new Exception('该老师和机构不存在关系！');
		$eventInfos = db()->fetchAll("select t_course_teacher.id as courseId,t_event.* from t_course_teacher left join t_event on t_course_teacher.event = t_event.id where t_event.school = $school AND t_course_teacher.teacher=$teacher");
    	db()->begin();
    	try{
	    	if($eventInfos){
	    		$courseIds = array();
	    		foreach($eventInfos as $eventInfo){
	    			load_model('teacher_course')->delete(array('id'=>$eventInfo['courseId']),true);
	    		}	
	    	}
			if(!load_model('school_teacher')->delete(array('school'=>$school,'teacher'=>$teacher),true)) throw new Exception('老师脱离机构失败！');
			if(load_model('school_group_teacher',array('table'=>'school_group_teacher'))->getRow(array('school'=>$school,'teacher'=>$teacher))){
				if(!load_model('school_group_teacher',array('table'=>'school_group_teacher'))->delete(array('school'=>$school,'teacher'=>$teacher),true)) throw new Exception('老师脱离机构失败！');
			}
			db()->commit();
			if($eventInfos){
		    	foreach($eventInfos as $eventInfo){
		    		event_push($eventInfo,array($teacher),array(),2,array(
		    		    		'act'=>'delete',
								'source' => array(
			                        'old'=>array(
			                            'text' => $eventInfo['text'], 'is_loop' => $eventInfo['is_loop'], 'rec_type' => $eventInfo['rec_type'],
			                            'start_date' => $eventInfo['start_date'], 'end_date' => $eventInfo['end_date'],'school' => $eventInfo['school'],
			                   		)
			                   	)
		        	));
		    	}
		    }           
		    Out(1, '脱离成功！', array('school'=>$school,'teacher'=>$teacher));
    	}catch(Exception $e)
		{
			db()->rollback();			
			Out(0, $e->getMessage());
		}
	}
	
	
	function event_push($eventInfo,$teachers,$students,$type=0,$data=array(), $whole=0){
		$hash = md5($eventInfo['id']).rand(10000,99999);
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
				'is_loop' => $eventInfo['is_loop'],
				'whole' => $whole,
				'school'=> $eventInfo['school'],
			),
			'data' => array(),
			'type'=>$type,
		);
		if($data['source']){
			$logsData['source'] = array_merge($logsData['source'],$data['source']);
			unset($data['source']);
		}
		$logsData = array_merge($logsData,$data);
		
		if($teachers){
			logs('db')->add('event', $hash,array_merge($logsData,array('character'=>'teacher','target'=>$teachers)));
		}
		if($students){
			logs('db')->add('event', $hash,array_merge($logsData,array('character'=>'student','target'=>$students)));
		}
	}	
}