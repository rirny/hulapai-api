<?php
class Teacher_Api extends Api
{
	public function __construct(){
		$cache = Http::post('tm', 'int', 0);
		$this->cache = $cache ? false : true;
	}

	// 老师主页
	public function index()
	{
		parent::_init();		
		$user = load_model('user')->getRow($this->uid);
		if(!$user) show_error('没有此用户！');
		$teacher = load_model('teacher')->getRow(array('user' => $this->uid));
		if(!$teacher) show_error('没有此老师！');
		
		$where = array(
			'teacher' => $this->uid,
			'character' => 'student',
			'pid,>' => 0,
			'create_time,>=' => date('Y-m-d', time()) . " 00:00:00"
		);

		$flower = load_model('comment')->getRow($where, false, 'sum(flower) s');

		$result = array(
			'id' => $this->uid,
			'account' => $user['account'],
			'name' => $user['firstname'] . $user['lastname'],
			'flower' => $teacher['flower'],
			'today' => current($flower)
		);
		Out(1, 'success', $result);
	}

	public function flower()
	{
		parent::_init();
		$where = array(
			'teacher' => $this->uid,
			'character' => 'student',
			'pid,>' => 0,
			'flower,>' => 0
		);

		$page = (int) Http::post('page', 'int', 0);
		$perpage = Http::post('per', 'int', 20);		
		$page = $page > 1  ?$page : 1;

		$order = 'create_time Desc';

		$limit = (($page - 1) * $perpage) . "," . $perpage;
		$total = load_model('comment')->getCount($where, 'count(id)');
		$data = load_model('comment')->getAll($where, $limit, $order, false, false, 'id,pid,content,flower,create_time,creator,student,teacher');

		$relations = array( 2 => '爸爸', 3 => '妈妈', 4 => '家长');
		array_walk($data, function(&$v) use($relations){
			$relation = load_model('user_student')->getRow(array('user' => $v['creator'], 'student' => $v['student']), false, 'relation');			
			$student = load_model('student')->getRow($v['student'], false, 'id,name,avatar');
			if($student && $relation)
			{
				$rs = $relation['relation'];
				$v['name'] = $student['name'];				
				$v['name'] .= empty($relations[$rs]) ? '' : $relations[$rs];
				$v['avatar'] = $student['avatar'];
				$v['parent'] = $v['creator'];
				$v['content']= "献给您" . $v['flower'] . "朵鲜花";
				unset($v['creator']);
			}
		});
		$page =  array('page'=>$page, 'total'=> $total, 'size'=>$perpage, 'pages'=> ceil($total / $perpage));
		Out(1, 'success', array('result' => $data, 'page' => $page));
	}
}