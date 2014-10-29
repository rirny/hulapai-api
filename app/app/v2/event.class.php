<?php

// 课程 招生
class Event_Api extends Api
{

	public function __construct(){
		$cache = Http::post('tm', 'int', 0);
		$this->cache = $cache ? false : true;
	}

	private $_targets = array('儿童', '成人');
	private $_forms = array('一对一', '精品小班');

	public function getTargets()
	{
		Out(1, 'success', $this->_targets);
	}
	public function getForms()
	{
		Out(1, 'success', $this->forms);
	}	
	private $_exprie = 1800;

	// 课程列表
	public function getList()
	{
		$school = Http::post('school', 'int', 0);
		$_School = load_model('school')->getRow($school);

		$page = Http::post('page', 'int', 1);		// 页
		$per = Http::post('per', 'int', 20);		// 每页
		intval($per)< 1 || $per = 20;

		$cache_key = 'school_recruit_list_' . $school . "_" . $page;

		$result = cache()->get($cache_key);
		if($result === false || $this->cache === false)
		{
			$_Recruit = load_model('recruit');
			$school = Http::post('school', 'int', 0);
			$limit = $_Recruit->getLimit($per, $page);
			$events = $_Recruit->getAll(array('school' => $school, 'status' => 0), $limit, 'modify_time Desc', false, false, 'id,`text`,`course`, `target`,`form`'); // 时间
			$total = $_Recruit->getCount(array('school' => $school, 'status' => 0), 'count(*)'); // 时间			
			$targets = $this->_targets;
			$forms = $this->_forms;
			array_walk($events, function(&$v,$k) use($targets, $forms){
				$v['target'] = explode(',', $v['target']);	
				array_walk($v['target'], function(&$tv) use($targets){
					$tv = $targets[$tv];
				});
				$v['form'] = explode(',', $v['form']);	
				array_walk($v['form'], function(&$tv) use($forms){
					$tv = $forms[$tv];
				});
			});
			$page =  array('page'=>$page, 'total'=> $total, 'size'=>$per, 'pages'=> ceil($total / $per));
			$result = compact('events', 'page');
			if($this->cache) cache()->set($cache_key, $result, $this->_exprie);
		}
		if(!$result) throw new Exception('没有课程！');
		Out(1, 'sucess', $result);
	}

	public function info()
	{
		$id = Http::post('event', 'int', 0);
		$cache_key = 'school_recruit_' . $id;
		$result = cache()->get($cache_key);
		if($result === false || $this->cache === false)
		{
			$result = load_model('recruit')->getRow(array('id' => $id), false, 'id,`text`,course,teacher,school,times,target,form,start_date,end_date,start_time,end_time,lb_price,ub_price,always,address,description,`status`');	
			$targets = $this->_targets;
			$forms = $this->_forms;
			$result['target'] = explode(',', $result['target']);			
			array_walk($result['target'], function(&$tv) use($targets){
				$tv = $targets[$tv];
			});
			$result['form'] = explode(',', $result['form']);	
			array_walk($result['form'], function(&$tv) use($forms){
				$tv = $forms[$tv];
			});
			$result['time'] = $result['start_time'] .'-'. $result['end_time'];
			unset($result['start_time'], $result['end_time']);
			$result['price'] = $result['lb_price'] .'-'. $result['ub_price'];
			unset($result['lb_price'], $result['ub_price']);
			$result['teacher'] = json_decode($result['teacher'],true);
			array_walk($result['teacher'], function(&$v, $k){
				$v['mind'] = current(load_model('teacher')->getColumn($k, 'mind'));
				$v['id'] = $k;
			});
			$result['teacher'] = array_values($result['teacher']);
			if($this->cache) cache()->set($cache_key, $result, $this->_exprie);			
		}
		if(!$result) throw new Exception('没有此课程');
		Out(1, 'sucess', $result);
	}

	// 预约报名
	public function reserve()
	{
		parent::_init();
		$student = Http::post('student', 'int', 0);
		$_Student = load_model('student')->getRow($student);
		if(!$_Student) throw new Exception('没有此学生！');
		$_User = load_model('user')->getRow($this->uid);
		if(!$_User) throw new Exception('未登录或者用户不存在！');
		$relation = load_model('user_student')->getRow(array('student' => $_Student['id'], 'user' => $this->uid));
		if(!$relation) throw new Exception('没有此学生！');
		$recruit = Http::post('event', 'trim', 0); // 课程
		$_Recruit = load_model('recruit')->getRow($recruit);
		if(!$_Recruit) throw new Exception('课程错误！');
		$_School = load_model('school')->getRow($_Recruit['school']);		
		if(!$_School) throw new Exception('没有此课！');
		// lead
		$lead = load_model('student_resource')->getRow($m = array(
			'school' => $_School['id'],
			'student'=> $student,
			'ext' => $recruit
		));
		if($lead) throw new Exception('已预约此课程，请不要重复申请！');
		$school_student = load_model('school_student')->getRow(array('student' => $_Student['id'], 'school' => $_Recruit['school']));
		$data = array(
			'source' => 4,//getLeadSource	
			'student' => $_Student['id'],	
			'gender' => $_Student['gender'],
			'name'	=> $_Student['name'],
			'birthday' => $_Student['birthday'],
			'create_time' => time(),
			'ext' => $_Recruit['id'],
			'course' => json_encode($recruit['course']),
			'school' => $_Recruit['school'],
			'user' => $this->uid,
			'parents' => json_encode(array(array(
				'id' => $_User['id'],
				'relation' => $relation['relation'],
				'name' => $_User['firstname'] . $_User['lastname'],
				'phone'=> $_User['account']
			))),
			'sign' => ($school_student ? 1 : 0), // 已是本机构学生
			// status 0正常 1删除 2处理状态
		);
		$data['id'] = load_model('student_resource')->insert($data);
		if(!$data['id'])  throw new Exception('预约失败！');
		Out(1, '成功', $data);
	}
	
	// 预约选择学生 选择完后没有确定界面
	public function student()
	{
		parent::_init();
		$_User = load_model('user')->getRow($this->uid);
		if(!$_User) throw new Exception('未登录或者用户不存在！');		
		$recruit = Http::post('event', 'trim', 0); // 课程
		if(!$recruit) throw new Exception('错误的预约！');
		$relation = load_model('user_student')->getAll(array('user' => $this->uid), '', '', false, false, '`user`,student id,relation');
		if(!$relation) throw new Exception('没有学生');	
		$reserved = load_model('student_resource')->getColumn(array('user' => $this->uid, 'ext' => $recruit), 'student'); // 已预约的学生		
		array_walk($relation, function(&$v,$key) use($reserved) {			
			$student = load_model('student')->getRow($v['id']);
			$v['name'] = $student['name'];
			$v['avatar'] = $student['avatar'];
			$v['reserved'] = in_array($v['id'], $reserved)? 1 : 0; // 是否预约
		});
		Out(1, '', $relation);
	}	
}