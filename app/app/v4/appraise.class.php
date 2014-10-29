<?php
/*
 * 评价
*/
class Appraise_Api extends Api
{
	public function __construct(){
		parent::_init();
		$this->refresh = Http::post('refresh', 'trim', 0);
	}

	public function index()
	{		
		$content = Http::post('content', 'int', 0);
		$student = Http::post('student', 'int', 0);
		$attach = Http::post('attach', 'trim', '');
		$teacher = Http::post('teacher', 'int', 0);
		db()->begin();
		try{
			if(!$content) throw new Exception('内容不能为空！');
			if(!$teacher || !$student) throw new Exception('参数错误');

			$res = load_model('teacher_student')->where('teacher', $teacher)->where('student', $student)->Row();
			if($res) throw new Exception('没有此老师');

			$create_time = DATETIME;
			$_Comment = load_model('comment', Null, true);
			$data = compact('student', 'attacch', 'teacher', 'content');
			$data['creator'] = $this->uid;
			$data['create_time'] = $create_time;
			$data['character'] = 'student';
			$data['target'] = 'teacher';

			$id = $_Comment->insert($data);
			if($id) throw new Exception('参数错误');
			
			// 推送
			$res = push('db')->add('H_PUSH', $logs = array(
				'app' => 'appraise',	
				'act' => 'add', 
				'from' => $this->uid,	
				'to'=> $teacher, 
				'type' => 2, 
				'student' => $student,
				'character' => 'student', 
				'ext' => array('comment' => $id)				
			));

			if(!$res) throw new Exception('评价失败！');
		
			db()->commit();
		}catch(HlpException $e){
			db()->rollback();
			Out(0, $e->getMessage());
		}		
	}

	/*
	 * 获取当前老师的评价
	*/
	public function get_list()
	{
		$teacher = Http::post('teacher', 'int', 0);
		$page = Http::post('page', 'int', 1);
		$perpage = 20;
		$teacher || $teacher = $this->uid;
		// $field = 'id,from,to,student,content,attatch,create_time';
		
		$_Comment = load_model('comment', Null, true)->where('pid', 0)
				->where('teacher', $this->uid)
				//->where('target', 'teacher')
				->where('character', 'student')
				->where('sid', 0);
		$page = $_Comment->Page();
		$field = 'id,teacher,creator,student,content,character,attach,create_time';
		$data = $_Comment->field($field)->limit($perpage, $page)->Result();
		foreach($data as $key=> &$item)
		{
			$item = $_Comment->formatForApp($item);
		}
		Out(1, 'success', compact('page', 'data'));
	}
}