<?php
class Grade_Api extends Api
{
	public $app = '';
	public $act = '';
	
	public function __construct(){
		parent::_init();
	}

	// 班级列表
	public function getList()
	{
		$result = load_model('grade')->getAll(array('teacher' => $this->uid), '', '', false, true, 'id,name,creator');		
		if($result)
		{	
			foreach($result as &$item)
			{
				$students = load_model('grade_student')->getColumn(array('grade' => $item['_id']), 'student');
				$item['students'] = join(",", $students);
			}
		}		
		// if(!$result) throw new Exception('还没有班级！');
		Out(1, '', $result);
	}
    
	// 添加班级
	public function add()
	{
		$name = Http::post('name', 'string', '');
		if(!$name) throw new Exception('请填写班级名称！');
		$_Grade = load_model('grade');
		$res = $_Grade->getRow(array('name' => $name, 'teacher' => $this->uid), false, 'id');
		if($res) throw new Exception('班级名称已存在！');
        $student = Http::post('student', 'string', 0);
		db()->begin();
		try
		{
			$data = array(
				'name' => $name,
				'teacher' => $this->uid,
				'creator' => $this->uid,
				'create_time' => time()
			);
			$id = $_Grade->insert($data);
			$student = Http::post('student', 'string', '');
			if($student)
			{
				$students = explode(',', $student);
                $tm = time();
				foreach($students as $key => $item)
				{
					$data = array(
						'grade' => $id, 
                        'student' => $item, 
                        'creator' => $this->uid, 
                        'create_time' => $tm
					);
					$res = $_Grade->add_student($id, $item, $this->uid);
					if(!$res) throw new Exception("添加失败！@Err.grade.addstudent[grade:{$id},student:{$item}]");
				}
			}
			if(!$id) throw new Exception('班级创建失败！');
			db()->commit();
			$result = $_Grade->getRow($id, true, 'id, name,creator');
			Out(1, '成功！', $result);
		}catch(Exception $e)
		{
			db()->rollback();
			Out(0, $e->getMessage());
		}		
	}
	
	// 删除班级
	public function delete()
	{
		$id = Http::post('id', 'int', '');
		if(!$id) throw new Exception('班级不存在！');
        Out(0, '班级不能删除！');
		$_Grade = load_model('grade');		
		db()->begin();
        $tm = time();
        $date = date('Y-m-d H:i:s');
        $_Event = load_model('event');
        $_Event_student = load_model('student_course');
        $_Event_teacher = load_model('teacher_course');
        $_Event_grade = load_model('event_grade', array('table' => 'event_grade'));     
        $_Grade_student = load_model('grade_student', array('table' => 'grade_student'));
		try
		{
            $res = $_Grade->getRow(array('teacher' => $this->uid, 'id' => $id), false, 'id');           
            if(!$res) throw new Exception('班级不存在！@Er.grade.delete[null]');        
            $push = array();
            // 删除这个班的课程
            $events = $_Event->getAll(array('grade' => $id, 'pid' => 0));          
            foreach ($events as $item)
            {
                $relations = $_Event_student->getAll(array('event' => $item['id']));
                foreach($relations as $relation)
                {
                    $student = $relation['student'];
                    isset($push[$student]) || $push[$student] = array();
                    $_Event_student->cut_relation($item, $relation, 0, $push[$student]);
                }                
                $_Event->delete($item['id']); // 删除课程
                $_Event_teacher->delete(array('event' => $item['id'])); // 删除老师课程
            }            
            // 删除并推送学生
            $resource = $_Grade_student->getColumn(array('grade' => $id), 'student');
            foreach($resource as $val)
            {  
                $res = load_model('student')->push($val, array(
                     'app' => 'grade',	'act' => 'delete',	'from' => $this->uid,
                     'character' => 'teacher', 'type' => 0, 'ext' => array(
                         'grade' => $id,
                         'event' => isset($push[$val]) ? $push[$val] : array()
                     )
                 ));
                if(!$res) throw new Exception("删除失败，课程下的学生删除失败！@Er.grade.delete[student:{$student}]");
            }
            $_Grade_student->delete(array('grade' => $id), true);  // 删除班级学生关系
            $_Event_grade->delete(array('grade' => $id), true);    // 删除班级课程关系
            $res = $_Grade->delete($id, true);
            if(!$res) throw new Exception('删除失败！@Er.grade.delete[grade:{$item}]');			
			db()->commit();
		}catch(Exception $e)
		{
			db()->rollback();
			Out(0, '删除失败！');
		}		
		Out(1, '成功！');
	}
	// 更新班级
	public function update()
	{
		$id = Http::post('id', 'int', 0);
		if(!$id) throw new Exception('参数错误！');
        db()->begin();
        $_Grade = load_model('grade');
        $grade = $_Grade->getRow($id, false, 'id');
        if(!$grade) throw new Exception('班级不存在！');
        $param = Http::query();       
        try
        {            
            if(isset($param['name']))
            {
                if(empty($param['name'])) throw new Exception('班级名称不能为空！');
                $_Grade->update(array('name' => $param['name']), $id);
            }
            if(isset($param['student']))
            {          
                $students = array();
                if(!empty($param['student']))
                {                    
                    $students = explode(',', $param['student']);
                }               
                $resource = $result = db()->fetchCol("select student from `t_grade_student` where `grade`='" . $id . "'");               
                $new = $resource ? array_unique(array_diff($students, $resource)) : $students;			
                $tm = time();
                if($new)
                {
                    $tm = time();
                    foreach($new as $item)
                    {
                        $res = load_model('teacher_student')->getRow(array('student' => $item, 'teacher' => $this->uid));
                        if(!$res) throw new Exception("没有此学生！@Er.student[{$item}]");
                        $res = $_Grade->add_student($id, $item, $this->uid);
                        if(!$res) throw new Exception("修改失败！@Err.grade.add.student[grade:{$id},student:{$item}]");
                    }
                }
                $lost = $resource ? array_unique(array_diff($resource, $students)) : array();        
				
                if($lost)
                {
                    foreach($lost as $item)
                    {
                        $res = $_Grade->remove_student($id, $item, $this->uid);
                        if(!$res) throw new Exception("修改失败！@Err.grade.remove.student[grade:{$id},student:{$item}]");
                    }
                }
            }
            db()->commit();
            Out(1, '成功！');
        }  catch (Exception $e)
        {
            db()->rollback();
            Out(0, $e->getMessage());
        }
		
	}

	// 添加学员
	public function add_student()
	{
		$grade = Http::post('grade', 'int', 0);
		if(!$grade) throw new Exception('参数错误！@Er.param.grade');
		$student = Http::post('student', 'string', '');
		if(!$student) throw new Exception('参数错误！@Er.param.student');
		$_Grade = load_model('grade');
		$students = explode(",", $student);		
		db()->begin();
		try
		{
			foreach($students as $item)
			{
				if($_Grade->student_exists($grade, $item)) throw new Exception('学生已在班级！');
				$data = array(
					'grade' => $grade, 'student' => $item, 'creator' => $this->uid, 'create_time' => time()	
				);
				$res = $_Grade->add_student($data);
				if(!$res) throw new Exception('添加失败！@Err.grade.addstudent[grade:{$grade},student:{$item}]');
			}	
			$result = $_Grade->get_student($grade, $student, true);
			db()->commit();
		}catch(Exception $e){
			db()->rollback();
			out(0, $e->getMessage());
		}		
		Out(1, '成功！', $result);
	}

	// 删除学员
	public function remove_student()
	{
		$id = Http::post('id', 'int', 0);
		if(!$id) throw new Exception('参数错误！');
		$student = Http::post('student', 'string', 0);
		if(!$student) throw new Exception('参数错误！');
		$_Grade = load_model('grade');
		if(!$_Grade->sutdent_exists($id, $student)) throw new Exception('学生不存在！');
		$res = $_Grade->remove_student($id, $student);
		if(!$res) throw new Exception('删除失败！');
		Out(1, '成功！');
	}

	public function exists_student()
	{
		$id = Http::post('id', 'int', 0);
		if(!$id) throw new Exception('参数错误！');
		$student = Http::post('student', 'int', 0);
		if(!$student) throw new Exception('参数错误！');
		if(!$_Grade->sutdent_exists($id, $student)) throw new Exception('不存在！');
		Out(1, '存在');
	}
}