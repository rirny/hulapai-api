<?php

class Course_Api extends Api
{

	public function __construct(){		
		// if($this->act != 'types') parent::_init();
	}

	// 子集
	public function childs()
	{
		$pid = Http::post('pid');
		Out(1, '', load_model('course')->childs($pid, true));
	}
	
	public function types()
	{		
		Out(1, '', load_model('course')->all_types(true));
	}
	
	public function info()
	{
		parent::_init();
		$id = Http::post('id');
		Out(1, '', load_model('course')->getRow($id));
	}	

	/* 列表
	 * @school
	 * @teacher
	*/
	public function getList()
	{
		parent::_init();
		$param = array('teacher' => $this->uid, 'status' => 0);	
		$_Course = load_model('course');
		$result = $_Course->getAll($param);
		if($result)
		{
			foreach($result as $key => $item)
			{
				$result[$key] = $_Course->Format($item);
			}
		}else{
			$message = '没有课程！';	
		}
		Out(1, $message, $result);
	}
	
	// 新建
	public function add()
	{	
		parent::_init();
		$type = Http::post('type', 'int', 0);
		$title = Http::post('title', 'string', '');
		$experience = Http::post('experience', 'int', 0);
		$fee = Http::post('fee', 'float', '0.00');
		$class_time = Http::post('class_time', 'int', 0);
		$create_time = time();
		$teacher = $this->uid;
		$creator = $this->uid;
		$operator = $this->uid;		
		if(!$type) throw new Exception('未选择课程分类！');
		// if(!$title) throw new Exception('未设置课程标题');
		$data = compact('type', 'title', 'fee', 'class_time', 'teacher', 'creator', 'operator', 'experience', 'create_time');		
		$_Course = load_model('course');
		$id = $_Course->insert($data);
		if(!$id) throw new Exception('未设置课程标题');
		$result = $_Course->getRow($id, true);
		out(1, '成功', $result);
	}

	// 修改
	public function update()
	{
		parent::_init();
		$type = Http::post('type', 'int', 0);
		$title = Http::post('title', 'string', '');
		$experience = Http::post('experience', 'int', 0);
		$fee = Http::post('fee', 'float', '0.00');
		$class_time = Http::post('class_time', 'int', 0);		
		$operator = $this->uid;
		$creat_time = time();
		$id = Http::post('id', 'int', 0);		
		if(!$id) throw new Exception('未选择课程');
		if(!$type) throw new Exception('未选择课程分类！');
		if(!$title) throw new Exception('未设置课程标题');
		// if(!$experience) throw new Exception('请设置教学年限！');
		$data = compact('type', 'title', 'fee', 'class_time', 'creator', 'experience', 'operator');		
		if(!load_model('course')->update($data, array('id'=> $id, 'teacher' => $this->uid))) throw new Exception('修改失败');
		out(1, '成功');
	}
	
	// 删除
	public function delete()
	{
		parent::_init();
		$id = Http::post('id', 'int', 0);
		if(!$id) throw new Exception('未选择课程');
		if(!load_model('course')->delete(array('id'=> $id, 'teacher' => $this->uid))) throw new Exception('删除失败');
		out(1, '成功');
	}
}