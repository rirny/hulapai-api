<?php
class Teacher_Api extends Api
{
	public function __construct(){
		parent::_init();
	}

	// 0普通用户 1家长 2机构 3分组
	public function info()
	{		
		$teacher = Http::post('id', 'int', 0);
		$character = Http::post('character', 'string', '');		 
		switch($character)
		{
			case 'parent': // 家长		
				$student = Http::post('student', 'int', 0);
				if(!$student) throw new Exception('学生不存在');
				$res = load_model('user_student')->getRow(array('user' => $this->uid, 'student' => $student), true);
				if(!$res) throw new Exception('没有该学生');				
				$relation = load_model('teacher_student')->getRow(array('teacher' => $teacher, 'student' => $student), true);
				if(!$relation) throw new Exception('没有该老师');				
				break;			
			case 'school': //机构
				$school = Http::post('school', 'int', 0); // 机构
				$relation = load_model('school_teacher')->getRow(array('school' => $school, 'teacher' => $teacher), true);
				if(!$relation) throw new Exception('没有该学生');
				break;			
			default : // 自己
				$relation = 0;
				// $teacher = $this->uid;
				break;
		}		
		$result = load_model('teacher')->getRow(array('user' => $teacher, 'status' => 0), true, 'province,city,area,address,target,background,mind,classes,comments,goods,flower');	
        if(!$result) throw new Exception('老师不存在');
        $user = load_model('user')->getRow($teacher, true, 'id,hulaid,nickname,gender,avatar,firstname,lastname');        
        if(!$user) throw new Exception('老师不存在!@Er.user');  
        $result = array_merge($result, $user);
		isset($relation) && $result['relation'] = $relation;
        $result['course'] = load_model('course')->getAll(array('teacher' => $teacher, 'status' => 0), '', '', false, true, 'id,title,experience,`type`');
        // 点评
        $result['comments'] = load_model('comment')->getRow(array('event' =>0, 'teacher' => $teacher, 'character,!=' => 'teacher','pid' => 0), false, '*', 'create_time Desc');
		Out(1, '', $result);
	}
	
	// 0普通用户 1家长 2机构 3分组	
	public function getList()
	{
		$student = Http::post('student', 'int', 0);		
		$character = Http::post('character', 'string', '');
		$school = Http::post('school', 'int', 0);		 
		$type = Http::post('type', 'int', 0);
		switch($character)
		{		
			case 'parent': // 家长
				if(!$student) throw new Exception('未指定学生！');
				$res = load_model('user_student')->getAll(array('user' => $this->uid, 'student' => $student), '','',false, true);
				if(!$res) throw new Exception('没有该学生');
				$result = load_model('teacher_student')->getAll(array('student' => $student, 'type' =>0 ), '','',false, true);

				break;
			case 'school': // 机构下的老师

				$sql = "select sch.id,sch.code,sch.avatar,sch.name,tch.id tch_id, tch.firstname, tch.firstname_en, tch.lastname, tch.lastname_en, tch.hulaid,tch.avatar tch_avatar,r.teacher_name from t_teacher_student r";
				$sql.= " Left join t_school sch on sch.id=r.ext";
				$sql.= " Left join t_user tch on tch.id=r.teacher";
				$sql.= " where r.`type`=1 And r.student=" . $student;
				$school && $sql.= " And r.ext='" . $school . "'";				
				$sql.= " order by r.create_time Desc";		
				$resource = db()->fetchAll($sql);					
				$result = array();
				foreach($resource as $item)
				{
					isset($result[$item['id']]) || $result[$item['id']] = array(
						'_id' => $item['id'],
						'name' => $item['name'],
						'code' => $item['code'],
						'avatar' => $item['avatar'],
						'has_comment' => $this->hasScComment($item['id']),
						'teachers' => array()
					);
					$result[$item['id']]['teachers'][] = array(
						'_id' => $item['tch_id'],	
						'hulaid' => $item['hulaid'],
						'firstname' => $item['firstname'],
						'lastname' => $item['lastname'],
						'teacher_name' => $item['teacher_name'],
						'avatar' => $item['tch_avatar'],
						'has_comment' => $this->hasComment($item['tch_id'])
					);
				}		
		
				$result = array_values($result);

				/*
				if(!$school) throw new Exception('未知机构！');
				$result = load_model('school_teacher')->getAll(array('school' => $school), '','',false, true);
				if(!$result) throw new Exception('没有老师');
				*/
				break;			
			case 'group':				
				$group = Http::post('group', 'int', 0); // 分组
				if(!$group) throw new Exception('没有该分组');
				$result = load_model('group_teacher')->getRow(array('group' => $group), true);
				if(!$result) throw new Exception('没有老师');
				break;
			default :
				break;
		}
		if($result && $character != 'school')
		{
			foreach($result as $key => & $item)
			{	
				$item['has_comment '] = $this->hasComment($item['teacher']);
				$item['teacher'] = load_model('user')->getRow(array('id' => $item['teacher']), true, 'id,hulaid,nickname,avatar,gender,firstname,lastname');	
			}
		}
		Out(1, '', $result);
	}

	private function hasComment($teacher){
		$comment = load_model('comment')->getRow(array(
			'teacher' => $teacher, 
			'character' => 'student',
			'event' => 0,
			'pid' => 0					
		));
		if($comment) return 1;
		return 0;
	}

	private function hasScComment($school){
		$comment = load_model('comment')->getRow(array(
			'school' => $school, 
			'character' => 'student',
			'event' => 0,
			'pid' => 0					
		));
		if($comment) return 1;
		return 0;
	}
	// 创建老师档案
	public function add()
	{		
		$param = Http::query();
		$_Teacher = load_model('teacher');
		$res = $_Teacher->getRow(array('user' => $this->uid));
		if($res) throw new Exception('档案已存在！');

		$data = array(
			'user' => $this->uid,
			'province' => Http::post('province', 'int', 0),
			'city' => Http::post('city', 'int', 0),
			'area' => Http::post('area', 'int', 0),
			'target' => Http::post('target', 'int', 0),
			'background' => Http::post('background', 'string', ''),
			'mind' => Http::post('mind', 'string', ''),
			'create_time' => time(),
			'agent' => Http::getSource()
		);

		db()->begin();
		try
		{
			$id = $_Teacher->insert($data);
			if(!$id) throw new Exception('创建失败！');			
			$user = array();
			load('ustring');
			isset($param['nickname']) && $user['nickname'] = $param['nickname'];
			isset($param['firstname']) && $user['firstname'] = $param['firstname'];
			isset($param['lastname']) && $user['lastname'] = $param['lastname'];

			empty($user['nickname']) || $user['nickname_en'] = Ustring::topinyin($user['nickname']);
			empty($user['firstname']) || $user['firstname_en'] = Ustring::topinyin($user['firstname']);
			empty($user['lastname']) || $user['lastname_en'] = Ustring::topinyin($user['lastname']);
			!empty($user['firstname']) || !empty($user['lastname']) && $user['name'] = $param['firstname'] . $param['lastname'];

			if($user)
			{
				$user['teacher'] = 1;
				$res = load_model('user')->update($user, $this->uid);
			}
			db()->commit();            
			$result = $_Teacher->getRow($id, true);
            $user = load_model('user')->getRow($this->uid, false, 'firstname,lastname');            
			Out(1, '成功', array_merge($result, $user));
		}catch(Exception $e)
		{
			db()->rollback();
			Out(0, $e->getMessage());
		}
	}

	public function update()
	{
		$param = Http::query();
		$_Teacher = load_model('teacher');		
		$info = $_Teacher->getRow(array('user'=>$this->uid)); // province city target background mind
		if(empty($info)) throw new Exception('老师档案不存在！');
		$data = array();
		foreach($param as $key => $value)
		{
			array_key_exists($key, $info) && $data[$key] = $value;
		}
		$user = array();
		load('ustring');
		isset($param['firstname']) && $user['firstname'] = $param['firstname'];
		isset($param['lastname']) && $user['lastname'] = $param['lastname'];		
		empty($user['firstname']) || $user['firstname_en'] = Ustring::topinyin($user['firstname']);
		empty($user['lastname']) || $user['lastname_en'] = Ustring::topinyin($user['lastname']);
		!(empty($user['firstname']) || empty($user['lastname'])) && $user['name'] = $param['firstname'] . $param['lastname'];
		db()->begin();
		try
		{
			$user && load_model('user')->update($user, $this->uid);           
			$data && $_Teacher->update($data, '`user`=' . $this->uid);
			db()->commit();
			Out(1, '成功');
		}catch(Exception $e){
			db()->rollback();
			Out(0, $e->getMessage());
		}
	}
	// 更新信息
	public function refresh()
	{
		$_Teacher = load_model('teacher');		
		$info = $_Teacher->getRow(array('user'=>$this->uid));
		$stat = $_Teacher->stat($this->uid);
		$result = array_merge($info, $this->uid);
		Out(1, '', $result);
	}
    
    
    public function delete()
    {
        $student = Http::post('student', 'int', 0);
        $teacher = Http::post('teacher', 'int', 0);
        $character = Http::post('character', 'string', '');
        if(!$teacher)   throw new Exception('删除失败！@Er.param[teacher]');        
        if($character == 'parent' || $character == 'student') // 家长删除老师
        {
            if(!$student)  throw new Exception('删除失败！@Er.param[student]');
            if(!load_model('student')->getRow(array('id' => $student, 'creator' => $this->uid))) throw new Exception('学生不存在或没有权限！@Er.param[student]');            
            $res = load_model('teacher_student')->delete(array(
                'teacher' => $teacher,
                'student' => $student
            ), true);
            // 推送给老师
            $res = push('db')->add('H_PUSH', $teacher_push = array(
                'app' => 'student',	'act' => 'delete',	'from' => $this->uid,	'type' => 0, 'to' => $teacher, 'student' => $student,
                'character' => 'user'
            ));
            // 推送给其他家长
            $res = load_model('student')->push($student, $student_push = array(
                'app' => 'teacher',	'act' => 'delete',	'from' => $this->uid,	'type' => 0,
                 'character' => 'user'
            ));
        }else if($character == 'group') // 从组里移除
        {            
            if(!$grade)   throw new Exception('参数错误！@Er.param[grade]');
            $res = db()->delete('t_group_teacher', array(
                'group' => $group,
                'teacher' => $teacher
            ));
        }else if($character == 'school') // 从机构里称除
        {
            if(!$school)   throw new Exception('参数错误！@Er.param[school]');
            $res = db()->update('t_school_student', array('status' => 1), array(
                'school' => $school,
                'teacher' => $teacher
            ));           
        }else{ // 删除档案
            throw new Exception('删档不能删除！');
            $res = load_model('teacher')->delete(array('user'=>$this->uid));
            // 通知所有学生
            $students = load_model('teacher_student')->getAll(array('student' => $student, 'status' => 0));
            foreach($students as $item)
            {
                $res = load_model('student')->push($student, array(
                    'app' => 'teacher',	'act' => 'delete',	'from' => $this->uid,	'type' => 0,
                    'character' => 'user'
                ), $this->uid);
                if(!$res) throw new Exception('删除失败！');
            }
        }
        if(!$res) throw new Exception('删除失败！');
        Out(1, '成功！');
    }

	public function getEventList()
	{		
		$teacher = $this->uid;
		$tm = Http::post('tm', 'int', 0);
		// if($teacher == 6566) $tm = 0;
		$result = load_model('teacher_course')->getSimpleEventList(compact('teacher', 'tm'), true);
		// if(!$result) throw new Exception('没有课程！');
		$lost = $tm ? load_model('delete_logs')->getColumn(array('app' => 'event', 'create_time,>=' => $tm, 'to' => $teacher,'student'=>0), 'ext') : array();
		die(json_encode(array('state' => 1, 'message' => '成功', 'result' => array('list' => $result, 'lost' => $lost)), JSON_NUMERIC_CHECK));
		// Out(1, '', $result);
	}
}