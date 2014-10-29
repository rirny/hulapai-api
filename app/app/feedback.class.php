<?php
class Feedback_Api extends Api
{
	public $app = '';
	public $act = '';
	
	public function __construct(){
		parent::_init();
	}
    
    public function add()
    {
        $to = Http::post('teacher', 'int', 0);
        $school = Http::post('school', 'int', 0);
        $student = Http::post('student', 'int', 0);
        $anonymous = Http::post('anonymous', 'int', 0);
        $content = Http::post('content', 'string', '');
        if(!$content) throw new Exception ('内容不能为空！@Er.param.content');
        
        if($student && !load_model('user_student')->getRow(array('student' => $student, 'user' => $this->uid))) throw new Exception ('没有此学生！@Er.param.student');	
        if($school) // 对机构评价
        {   
            $type =  1;
			if($student)
			{
				if(!load_model('school_student')->getRow(array('student' => $student, 'school' => $school))) throw new Exception ('不是机构的学生！@Er.param.school.student');
			}else{
				if(!load_model('school_teacher')->getRow(array('teacher' => $this->uid, 'school' => $school))) throw new Exception ('不是机构的老师！@Er.param.school.teacher');
			}
        }else if($to) // 对老师评价
        {
            $type =  2;
            if(!load_model('teacher_student')->getRow(array('student' => $student, 'teacher' => $to))) throw new Exception ('不是老师的学生！@Er.param.teacher.student');
			$res = push('db')->add('H_PUSH', array(
				'app' => 'feed',	'act' => 'add', 'from' => $this->uid, 'to'=> $to , 'student' => $student , 'type' => 1, 
				'character' => 'student', 'ext' => $content
			));
        }else{
			$type = 0;			
			// throw new Exception ('参数错误！@Er.param.teacher|school');
		}        
        $create_time = date('Y-m-d H:i:s');
        $from = $this->uid;
        $data = compact('from','to','type', 'school','student', 'anonymous', 'content', 'create_time');      
        $data['id'] = load_model('feedback', array('table' => 'feedback'))->insert($data);
        if(!$data['id']) throw new Exception ('提交失败！');        
        Out(1, '成功', $data);
    }
    
	// 反馈列表
	public function getList()
	{   
        $_FeedBack = load_model('feedback', array('table' => 'feedback'));
		$result = $_FeedBack->getAll(array('to' =>$this->uid, 'status' => 0), '', 'create_time Desc', false, true, 'id,`from`,school,`type`,student,content,anonymous,create_time');
        if($result)
        {
            foreach($result as $key => $item)
            {   
                $relation = load_model('user_student')->getRow(array('user'=> $item['from'], 'student' => $item['student']), true, 'relation');
                $item['student'] && $item['student'] = load_model('student')->getRow($item['student'], true, 'id,name,nickname,avatar'); 
                $item['from'] = load_model('user')->getRow($item['from'], true, 'firstname,lastname,account,hulaid,nickname,avatar');                
                $relation && $item['relation'] = $relation['relation'];                
                $result[$key] = $item;
            }
        }
		Out(1, '', $result);
	}
}