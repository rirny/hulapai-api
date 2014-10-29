<?php
/**
 * msgtype
 * SSdesc  学生
 * SSend
 */
class Student_Api extends Api
{

	public function __construct(){
		parent::_init();
	}
	
	/**
	 * structdef.xml
	 * 	SSaction     info
	 *  SSdesc       学生信息 
	 *  SSpargam 
	 * 		student   int   学生id
	 * 		identity  int   类型(0普通用户1、家长2、老师3机构4、班级)
	 *  SSreturn 
	 * 		_id				int			id
	 * 		name			varchar  	学生名
	 * 		nickname		varchar		昵称
	 * 		avatar			int 		头像最后更新时间
	 * 		gender			int			性别
	 * 		birthday		date		生日
	 * 		classes			int			创建时间
	 * 		absence			int		缺勤数
	 * 		leave			int			请假
	 * 		create_time		int			创建时间
	 * 		status			int 		状态：删除、锁定...
	 * 		operator		int			操作者
	 * 		tag				varchar		标签
	 * 		creator			int			创建者
	 * 		parent			array		家长信息  parent
	 * 		relation		singleArray       关系信息（identity=1,2时）	 relation
	 * SSreturn_array_parent
	 * 		_id			int    	id
	 * 		user		int   	 用户
	 * 		student		int		学生
	 * 		relation	int		1本人,2爸爸,3妈妈,4其他
	 * 		create_time	int		创建时间
	 * 		creator		int		创建者
	 * 		parent      array   家长详细信息  parent_parent
	 * SSreturn_array_end_parent
	 * SSreturn_array_parent_parent
	 * 		_id			int   id
	 * 		account		varchar  账号
	 * 		firstname	varchar		姓
	 * 		lastname	varchar		名
	 * 		email		varchar		邮箱
	 * 		nickname	varchar    昵称
	 * 		gender		int 		性别 0男，1女
	 * 		hulaid		varchar		呼啦号
	 * 		avatar		int		头像最后更新时间
	 * 		birthday	date		生日
	 * 		province	int		省
	 * 		city		int		市
	 * 		area		int		区
	 * 		address		varchar	地址
	 * 		create_time	int		注册时间
	 * 		login_salt	varchar		登录标
	 * 		mobile		int  手机
	 * 		status		int  0正常1冻结
	 * 		setting	    singleArray	个人设置	parent_parent_setting
	 * 		sign		varchar 	签名
	 * 		course_notice	int 	课程通知
	 * 		disturb		int		免打扰
	 * 		token		varchar		设备token
	 * SSreturn_array_end_parent_parent
	 * SSreturn_array_parent_parent_setting
	 * 		hulaid		varchar		呼啦号
	 * 		friend_verify	int 	是否允许加好友
	 * 		notice		singleArray	通知 	parent_parent_setting_notice
	 * SSreturn_array_end_parent_parent_setting
	 * SSreturn_array_parent_parent_setting_notice
	 * 		method		int
	 * 		types		varchar
	 * SSreturn_array_end_parent_parent_setting_notice
	 * SSreturn_array_relation
	 * 		_id		int		id
	 * 		user	int     用户
	 * 		relation  int   1本人,2爸爸,3妈妈,4其他
	 * 		student		int  学生
	 * 		teacher		int  老师
	 * 		classes		int   课时
	 * 		attend		int		出勤
	 * 		absence		int		缺勤
	 * 		leave		int		请假
	 * 		study_date	date		开学时间
	 * 		create_time	int		创建时间
	 * 		creator		int		创建者
	 * 		status		int		状态0正常1已取消
	 * SSreturn_array_end_relation
	 * SSend
	 */
	public function info()
	{
		$student = Http::Post('student', 'int', 0);
        $student || $student = Http::Post('id', 'int', 0);       
		$character = Http::Post('character', 'string', '');
        if(!$student)  throw new Exception('未指定学生');  
		switch($character)
		{			
			case 'parent': // 家长
				$relation = load_model('user_student')->getRow(array('user' => $this->uid, 'student' => $student), true);                
				if(!$relation) throw new Exception('没有该学生');
				break;
			case 'teacher': // 老师
				$relation = load_model('teacher_student')->getRow(array('teacher' => $this->uid, 'student' => $student), true);
				if(!$relation) throw new Exception('没有该学生');               
				break;
			case 'school':
				$school = Http::Post('school', 'int', 0); // 机构
				$relation = load_model('school_student')->getRow(array('school' => $this->uid, 'student' => $student), true);
				if(!$relation) throw new Exception('没有该学生');
				break;
			case 'grade':				
				$grade = Http::Post('grade', 'int', 0); // 班级
				if(!$grade) throw new Exception('没有该班级');
				$relation = load_model('grade')->get_student($grade, $student, true);
				if(!$relation) throw new Exception('没有该学生');
				break;
			default :
                           
				break;
		}
		$result = load_model('student')->getRow($student, true);		
		if(!$result) throw new Exception('学生不存在');
		$result['parent'] = load_model('user_student')->get_parents($student, true);
		isset($relation) && $result['relation'] = $relation;
        // $result['comments'] = load_model('comment')->getRow(array('event' =>0, 'student' => $student), '1', 'create_time Desc', FALSE, true, '');
		Out(1, '', $result);
	}

	/**
	 * structdef.xml
	 * 	SSaction     getList
	 *  SSdesc       学生列表 
	 *  SSpargam 
	 * 		id   int   手机号码
	 * 		character	int  类型(0:搜索1:家长2:老师3:机构4:班级5:课程)
	 * 		account		varchar   账号		
	 * 		uid		int   用户id
	 *  SSreturn 
	 *  SSend
	 */
	public function getList()
	{
		$id = Http::Post('id', 'int', 0);
		$character = Http::Post('character', 'string', '');
		$account = Http::Post('account', 'string', '');
		$uid = Http::Post('uid', 'int', 0);
		$result = array();
		$type = Http::Post('type', 'int', 0);
		$school = Http::Post('school', 'int', 0);

		switch($character)	
		{			
			case 'parent': // 家长
				if($account)
				{
					$res = load_model('user')->getRow("hulaid='". $account . "' Or `account`='" . $account . "'", false, 'id');					
					if(!$res) throw new Exception('用户不存在！');
					$uid = $res['id'];
				}
				$uid || $uid = $this->uid;
				$result = load_model('user_student')->getAll(array('user' => $uid), '', '', false, true);
				if(!$result) throw new Exception('没有学生');
				break;
			case 'teacher': // 老师		
				$uid || $uid = $this->uid; 
				$res = load_model('teacher')->getRow(array('user' => $uid), false, 'id');                
				if(!$res) throw new Exception('您没有老师档案!');
				$result = load_model('teacher_student')->getAll(array('teacher' => $uid, 'type' => 0), '', '', false, true);			
				if(!$result) throw new Exception('没有学生');
				break;
			case 'school': // 获取机构下的学生
				/*
				$school = Http::Post('school', 'int', 0); // 机构
				$result = load_model('school_student')->getAll(array('school' => $school), '', '', false, true);
				if(!$result) throw new Exception('没有学生');
				*/
				$uid || $uid = $this->uid; 
				$res = load_model('teacher')->getRow(array('user' => $uid), false, 'id');                
				if(!$res) throw new Exception('您没有老师档案!');
				$sql = "select sch.id,sch.code,sch.avatar,sch.name,stu.id stu_id, stu.name stu_name,stu.name_en stu_name_en,stu.avatar stu_avatar,r.student_name from t_teacher_student r";
				$sql.= " Left join t_school sch on sch.id=r.ext";
				$sql.= " Left join t_student stu on stu.id=r.student";
				$sql.= " where r.teacher=" . $uid; // r.`type`=1 And 
				if($school)
				{		
					$sql.= " And r.ext='" . $school . "'";					
				}
				$sql.= " order by r.create_time Desc";
				$resource = db()->fetchAll($sql);
				$result = array();
				foreach($resource as $item)
				{
					isset($result[$item['id']]) || $result[$item['id']] = array(
						'_id' => $item['id'],
						'name' => $item['name'],
						//'name_en' => $item['name_en'],
						'code' => $item['code'],
						'avatar' => $item['avatar'],
						'students' => array()
					);
					$result[$item['id']]['students'][] = array(
						'_id' => $item['stu_id'],	
						'name' => $item['stu_name'],
						'name_en' => $item['stu_name_en'],
						'student_name' => $item['student_name'],
						'avatar' => $item['stu_avatar'],
					);
				}				
				$result = array_values($result);
				if(!$result) throw new Exception('没有学生');
				break;
			case 'grade':				
				$grade = Http::Post('grade', 'int', 0); // 班级
				if(!$grade) throw new Exception('没有此班级！');
				$result = load_model('grade')->get_students($grade, true);                
				if(!$result) throw new Exception('没有学生');
				break;
			case 'event':
				$event = Http::Post('event', 'string', 0); // 课程
				if(!$event) throw new Exception('没有此课程！');
				if(strpos($event, '#'))
				{
					list($pid, $length) = explode("#", $event);
					if(!$pid || !$length) throw new Exception("错误的参数！@Er.event[{$event}]");
					$res = load_model('event')->getRow(array('pid' => $pid, 'length' => $length, 'rec_type,!=' => 'none'), false, 'id');
					$event = $res ? $res['id'] : $pid;
				}					
				$result = load_model('student_course')->getAll(array('event' => $event, 'status' => 0), '', '', false, true, 'student,`status`,absence,commented');				
				if(!$result) throw new Exception('没有此课');
				break;
			default :
				break;
		}
		if($result)
		{
			foreach($result as $key => $item)
			{
				$student = load_model('student')->getRow(array('id' => $item['student']), true, 'name,nickname,avatar,creator');
				$result[$key] = array_merge($item, $student);
			}
		}
		Out(1, '', $result);
	}

	/**
	 * structdef.xml
	 * 	SSaction     add
	 *  SSdesc       创建学生档案 
	 *  SSpargam 
	 * 		name   varchar   名字
	 * 		nickname	varchar  昵称
	 * 		gender		int   性别		
	 * 		birthday	date   生日
	 * 		relation	int   关系
	 *  SSreturn 
	 * 		_id			int   id 
	 * 		name		varchar  学生名
	 * 		nickname	varchar  昵称
	 * 		avatar		int   头像最后更新时间
	 * 		gender		int  性别
	 * 		birthday		date  生日
	 * 		classes		int    创建时间
	 * 		absence	int	缺勤数
	 * 		leave		int	请假
	 * 		create_time		int   创建时间
	 * 		status	int   状态：删除、锁定...
	 * 		operator		int		操作者  	
	 * 		tag	 varchar  标签
	 * 		creator		int   创建者
	 *  SSend
	 */
	public function add()
	{
		$name = Http::post('name', 'string');
		if(!$name)  throw new Exception('学生姓名不能为空!');
		$nickname = Http::post('nickname', 'string');
		$gender = Http::post('gender', 'int', 0);
		$birthday = Http::post('birthday', 'string', '0000-00-00');
		$relation = Http::post('relation', 'int', 1); // 默认本人
		$create_time = time();
		$operator = $this->uid;
		$creator = $this->uid;
		$tag = Http::post('tag', 'string', 0);
		$_Student = load_model('student');
		$agent = Http::getSource();		
		$data = compact('name', 'nickname', 'gender', 'birthday', 'create_time', 'tag', 'operator', 'creator', 'agent');		
		db()->begin();
		try{
			load('ustring');
			empty($data['nickname']) || $data['nickname_en'] = Ustring::topinyin($data['nickname']);
			empty($data['name']) || $data['name_en'] = Ustring::topinyin($data['name']);
			$id = $_Student->insert($data);
			if(!$id) throw new Exception('创建失败！');
			if(!empty($_FILES))
			{	
				import('file');
				Files::upload('student', 'image', $id);
				$_Student->update(array('avatar' => time()), $id);
			}	
			$res = load_model('user_student')->insert(array( // 建立联系
				'user' => $this->uid,
				'student' => $id,
				'relation'=> $relation,
				'create_time' => $create_time,
				'creator' => $this->uid
			));
			if(!$res) throw new Exception('创建失败！@Er.relation.create');
			db()->commit();
			$result = $_Student->getRow($id, true);
			Out(1, '创建成功！', $result);
		}catch(Exception $e)
		{
			db()->rollback();
			Out(1, '创建失败！');
		}		
	}

	// 学生信息修改
	public function update()
	{
		$param = Http::query();
		$_Student = load_model('student');
		$id = Http::post('id', 'int', 0);
		$id || $id = Http::post('student', 'int', 0);
		$student = $_Student->getRow(array('id' => $id, 'creator' => $this->uid), false);		
		if(empty($student)) throw new Exception('学生档案不存在！');
		$data = array();
		foreach($param as $key => $value)
		{
			array_key_exists($key, $student) && $data[$key] = $value;
		}		
		load('ustring');
		empty($data['nickname']) || $data['nickname_en'] = Ustring::topinyin($data['nickname']);
		empty($data['name']) || $data['name_en'] = Ustring::topinyin($data['name']);

		$_Student->update($data, $id);			
		Out(1, '成功');
	}
	
	// 统计数据
	public function stat()
	{
		return array(
			'classes' => 0,
			'absnece' => 0,
			'leave' => 0
		);
	}
    
	// 获取授权列表
	public function authList()
	{     
        $student = Http::post('student', 'int', 0);
        if(!$student)   throw new Exception('未指定学生');
        $relation = load_model('user_student')->getAll(array('student' => $student, 'user,!=' => $this->uid), '', '', false, true, 'id, student,`user`,relation');     
        foreach($relation as $item)
        {           
            $parent = load_model('user')->getRow($item['user'], false, 'hulaid,nickname,firstname,lastname,avatar');
            $result[] = array_merge($item, $parent);
        }
        Out(1, '', $result);
	}

	public function relationChange()
	{
		$student = Http::post('id', 'int', 0);
		if(!$student)   throw new Exception('未指定学生');
		$relation = Http::post('relation', 'int', 4);
		if($relation > 4 || $relation < 1) throw new Exception('错误的关系！');
		$relationShip = array(
			1 => '本人', 2 => '爸爸', 3 => '妈妈'
		);
		$res = load_model('user_student')->getRow(array('student' => $student, 'relation' => $relation));
		// 1本人,2爸爸,3妈妈		
		if($relation != 4 && $res && $this->uid != $res['user'])
		{
			throw new Exception("一个学生不能有两个[{$relationShip[$relation]}]关系");
		}
		load_model('user_student')->update(array('relation' => $relation), array('user' => $this->uid, 'student' => $student));
		Out(1, '成功！');
	}		
	
    public function delete()
    {
        $student = Http::post('student', 'int', 0);
        $character = Http::post('character', 'string', 'teacher');
        if(!$student)   throw new Exception('未指定学生');        
		$tm = time();
        $date = date('Y-m-d H:i:s', $tm);
        
		$creatorRs = load_model('student')->getRow($student);
		$creator = $creatorRs['creator'];
        $_Event = load_model('event');
        $_Event_student = load_model('student_course');
        db()->begin();
        try
        {        
            if($character == 'teacher') // 解除师生关系 老师删除学生
            {
                if(!load_model('teacher_student')->getRow(array('teacher' => $this->uid, 'student' => $student))) throw new Exception('没有此学生！');
                // 1、非子课程课程
                // 2、子课程                
                // 清理课程
                $sql = "select e.id,r.id rid from t_course_student r left join t_event e on r.event=e.id where e.`status`=0 AND r.`status`=0 AND e.creator={$this->uid} AND r.student=$student and e.pid=0";                
                $events = db()->fetchAll($sql);
                
                $push = array();               
                foreach($events as $item)
                {
                    $event = $_Event->getRow($item['id']);
                    $relation = $_Event_student->getRow($item['rid']);   
                    $res = $_Event_student->cut_relation($event, $relation, 0, $push); // 切断关系
                    if(!$res) throw new Exception("参数错误！@Er.cut[{$item['id']}]");                    
                }
                // 删除老师的所有班里，与这个学生的关系
                load_model('grade_student', array('table' => 'grade_student'))->delete(array('student' => $student, 'creator' => $this->uid), true);
                // 推送给家长                
                $rs = load_model('student')->push($student, $logs = array(
                    'app' => 'teacher',	'act' => 'delete',	'from' => $this->uid,	'type' => 0,
                    'character' => 'teacher', 'ext' => array('event' => $push)
                ));               
                
                if(!$rs)throw new Exception('参数错误！@Er.push[parent]');                
                // 删除关系
                load_model('teacher_student')->delete(array(
                    'teacher' => $this->uid,
                    'student' => $student
                ), true);
            }else if($character == 'parent') //删除授权、删除被授权
            {
                $user = Http::post('user', 'int', 0);
                if(!$user && $creator['creator'] == $this->uid) throw new Exception('参数错误！@Er.param[user]');                
                if($creator == $this->uid)  // 创建者删除授权
                {   
                    if(!$user) throw new Exception('参数错误！@Er.param[user]');
                    if($creator == $user) throw new Exception('学生档案不能删除！@Er.param[user]');
                    $res = load_model('user_student')->delete(array(
                        'user' => $user,
                        'student' => $student               
                    ), true);

                    // 推送授权方
                    $res = push('db')->add('H_PUSH', $logs = array(
                        'app' => 'auth',	'act' => 'delete',	'from' => $this->uid,	'type' => 0, 'to' => $user,
                        'student' => $student,  'character' => 'user'
                    ));
                    if(!$res) throw new Exception('删除失败！@Er.push[user]');
                    // 家长，所有这个学生的课程都清除
                }else{  // 家长删除授权的学生              
                    $user = $this->uid;
                    $res = load_model('user_student')->delete(array(
                        'user' => $user,
                        'student' => $student
                    ), true);                    
                    /*
                    $res = push('db')->push('H_PUSH', array(
                        'app' => 'auth',	'act' => 'delete',	'from' => $this->uid,	'type' => 0, 'to' => $creator['creator'],
                        'student' => $student,  'character' => 'user', 'ext' => array('user' => $user)
                    ));
                    */
                }
            }else if($character == 'grade') // 从班级里移除
            {            
                $grade = Http::post('grade', 'int', 0);
                if(!$grade)   throw new Exception('参数错误！@Er.param[grade]');
                $_Grade = load_model('grade');
                $resource = $_Grade->getRow(array('id' => $grade, 'creator' => $this->uid));
                if(!$resource) throw new Exception('没有此班级！@Er.param[grade]');
                $res = load_model('grade_student')->getRow(array('grade' => $grade, 'student' => $student));
                if(!$res) throw new Exception('班级里没有此学生！@Er.un_exists');
                $result = $_Grade->remove_student($grade, $student, $this->uid);
                if(!$result) throw new Exception('删除失败！');
            }else{ // 删除档案
                throw new Exception('学生档案不能删除!');
                $res = load_model('student')->delete('`id`=' . $student . " And `creator`=" . $this->uid);   
                if(!$res) throw new Exception('删除失败！');
                // 通知所有家长
                $res = load_model('student')->push($student, array(
                    'app' => 'student',	'act' => 'delete',	'from' => $this->uid,	'type' => 0,
                     'character' => 'user'
                ), $this->uid);            

                // 删除所有班级里的这个学生
                $_Event_grade = load_model('event_grade', array('table' => 'event_grade'));
                $_Grade_student = load_model('grade_student', array('table' => 'grade_student'));
                $_Grade_student->delete(array('student' => $student), true); // 删除所有班级的这个学生           
                $_Event_grade->delete(array('student' => $student), true); // 删除课程班级关系
                
                // 删除这个学生的所有课程
                $events = $_Event_student->getAll(array('student' => $student, 'status' => 0));
                $push_teacher = $push_parent = array();
                $teachers = array();
                foreach ($events as $item)
                {
                    $teacher = $event['teacher'];
                    $event = $_Event->getRow($item['event']);
					if($item['pid'] == 0)
					{
						$res = $_Event_student->cut_relation($event, $item, 0, array()); // 清除数据，保留已过去的数据
						if(!$res) throw new Exception('删除失败！@Er.relation.cut');
					}
                    if(is_array($res) && !empty($res))
                    {
                        $push_teacher[$teacher][] = $res;
                        $push_parent[] = $res;
                    }
                }            
                // 通知所有老师
                $teachers = load_model('teacher_student')->get_teacher(array('student' => $student, 'status' => 0));
                foreach($teachers as $item)
                {
                    $res = push('db')->push('H_PUSH', array(
                        'app' => 'student',	 'act' => 'delete',	 'from' => $this->uid,	'type' => 0, 'to' => $item, 'student' => $student,
                        'character' => 'user', 'ext' => isset($push_teacher[$item]) ? $push_teacher[$item] : array()
                    ));
                    if(!$res) throw new Exception('删除失败！@Er.push.teacher');
                }
                // 通知家长
                $res = load_model('student')->push($student, array(
                     'app' => 'student',    'act' => 'delete',	 'from' => $this->uid,	'type' => 0,
                     'character' => 'user', 'ext' => $push_parent                
                ));
                if(!$res) throw new Exception('删除失败！@Er.push.parent');
            }            
            db()->commit();
            Out(1, '成功！');
        }  catch (Exception $e)
        {
            db()->rollback();
            Out(0, $e->getMessage());
        }
    }
    
	public function getEventList()
	{		
		$student = Http::post('student', 'int', 0);
		$tm = Http::post('tm', 'int', 0);
		if(!$student) throw new Exception('未指定学生@Er.param.student');
		$result = load_model('student_course')->getSimpleEventList(compact('student', 'tm'), true);		
		$lost = $tm ? load_model('delete_logs')->getColumn(array('app' => 'event', 'create_time,>=' => $tm, 'to' => $this->uid, 'student' => $student), 'ext'):array();
		array_unique($lost);
		die(json_encode(array('state' => 1, 'message' => '成功', 'result' => array('list' => $result, 'lost' => $lost)), JSON_NUMERIC_CHECK));
	}
}