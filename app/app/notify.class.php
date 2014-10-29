<?php
/**
 * msgtype
 * SSdesc  通知模块
 * SSend
 */
class Notify_Api extends Api
{
	
	public function __construct(){
		parent::_init();
	}
	
	private $_logs = array(
		'hash' => '', 
		'app' => 'notify', 
		'act' => 'add', 
		'character'=> 'student',
		'creator' => '', 
		'target' => array(), 
		'ext' => array(),
		'source' => array(), 
		'data' => array(),
		'type'=>0,
	);	
	
	private function get_logs(array $param)
	{
		isset($param['hash']) || $this->_logs['hash'] = _hash(Http::query());
		isset($param['target']) || $this->_logs['target'] = '';
		isset($param['app']) || $this->_logs['app'] = $this->app;		
		isset($param['act']) || $this->_logs['act'] = $this->act;
		$this->_logs['creator'] = $this->uid;
		return array_merge($this->_logs, $param);		
	}
	

	public function index(){
		
	}

	/**
	 * structdef.xml
	 * 	SSaction     info
	 *  SSdesc       通知信息
	 *  SSpargam 
	 * 		id  int  通知id
	 *  SSreturn 
	 * 		id  int   通知id
	 * 		creator  singleArray 创建者用户  user
	 * 		type	int 类型（0系统1老师2机构）
	 * 		event  varchar  课程id
	 * 		create_time  varchar  创建时间
	 * 		student	array  接收学生id  []
	 * 		teacher	array  接收老师id  []
	 * 		content	varchar 内容
	 * 		attachs  array  附件信息 attachs
	 * 		vote   int  问卷id
	 * 		school  int 机构id
	 * 		receipt  int 是否需要回执
	 * 		status	int  状态
	 *  SSreturn_array_attachs
	 * 		attach_id  int   附件id
	 * 		attach_url  varchar 附件地址
	 * 		attach_small varchar 附件地址
	 * 		attach_middle varchar 附件地址
	 * 		domain  varchar   domain
	 *  SSreturn_array_end_attachs
	 *  SSend
	 */
	public function info()
	{
		$id = Http::post('id','int',0);
		if(!$id){
			out(0, '参数错误');
		}
		$notify = load_model('notify')->getRow($id);
		if(!$notify){
			out(0, '通知不存在');
		}
		$notify = $this->_info($notify);
		out(1, '',$notify);
	}
	
	private function _info($notify){
		if($notify['creator']){
			$notify['creator'] = load_model('user')->getRow($notify['creator'],false,'id,nickname,firstname,lastname,hulaid,avatar');
		}else{
			$notify['creator'] = array();
		}
		$notify['student'] = json_decode($notify['student'],true);
		$notify['teacher'] = json_decode($notify['teacher'],true);
		$notify['attachs'] = json_decode($notify['attachs'],true);
		if($notify['school']){
			$notify['school'] = load_model('school')->getRow($notify['school'],false,'id,name,code,pid,type,avatar');
		}else{
			$notify['school'] = array();
		}
		$notify['create_time'] = date('Y-m-d H:i:s',$notify['create_time']);	
		return $notify;
	}
	/**
	 * structdef.xml
	 * 	SSaction     getList
	 *  SSdesc       老师通知列表
	 *  SSpargam 
	 * 		page  int  页码
	 * 		event int  课程id(不传获得所有)
	 * 		student int  学生id(不传获得所有)
	 *  SSreturn 
	 * 		page  array  页码数组 page
	 * 		notifies  array  通知数组 notifies
	 *  SSreturn_array_page
	 * 		page  int  当前页码
	 * 		total int  总记录数
	 * 		size  int  每页显示
	 * 		pages int  总页数
	 *  SSreturn_array_end_page
	 *  SSreturn_array_notifies
	 * 		id  int   通知id
	 * 		creator  singleArray 创建者用户  user
	 * 		type	int 类型（0系统1老师2机构）
	 * 		event  varchar  课程id
	 * 		create_time  varchar  创建时间
	 * 		student	array  接收学生id  []
	 * 		teacher	array  接收老师id  []
	 * 		content	varchar 内容
	 * 		attachs  array  附件信息 attachs
	 * 		vote   int  问卷id
	 * 		school  int 机构id
	 * 		receipt  int 是否需要回执
	 * 		status	int  状态
	 *  SSreturn_array_end_notifies
	 *  SSreturn_array_notifies_attachs
	 * 		attach_id  int   附件id
	 * 		attach_url  varchar 附件地址
	 * 		attach_small varchar 附件地址
	 * 		attach_middle varchar 附件地址
	 * 		domain  varchar   domain
	 *  SSreturn_array_end_notifies_attachs
	 *  SSend
	 */
	public function getList()
	{
		$event = Http::post('event','int',0);
		$student = Http::post('student','int',0);
		$page = Http::post('page','int',0);
		$page = !$page || $page <= 0 ? 1 : $page;
		$character = "teacher";
		$student && $character = "student";
		$query = "1";
		if($character == "teacher"){
			$query = "(creator = $this->uid or `teacher` like '%\"$this->uid\"%')";
		}
		if($event){
			$query .= " and `event` = $event";	
		}
		if($student){
			$query .= " and `student` like '%\"$student\"%'";	
		}
		$total = load_model('notify')->getRow($query,false,'count(1) as num');
		$total = $total['num'];
		$pagesize = 20;
		$pages = ceil($total/$pagesize);
		$pages = $pages <= 0 ? 1 : $pages;
		$offset = ($page-1)*$pagesize;
		
		$notifies = load_model('notify')->getList($character,$this->uid,$event,$student,$offset,$pagesize);
		if($notifies){
			foreach($notifies as &$notify){
				$notify = $this->_info($notify);
			}
		}
		out(1, '', array('page'=>array('page'=>$page,'total'=>$total,'size'=>$pagesize,'pages'=>$pages), 'notifies'=>$notifies),1);
	}
	
	/**
	 * structdef.xml
	 * 	SSaction     add
	 *  SSdesc       老师发送通知
	 *  SSpargam 
	 * 		student  varchar  学生id,','分隔(必须)
	 * 		content  varchr  内容(必须)
	 * 		event   int   课程id
	 * 		attach   varchar  附件id,','分隔
	 * 		vote     int    问卷id
	 *  SSreturn 
	 * 		id	 int  通知id
	 *  SSend
	 */
	public function add()
	{
		$event = Http::post('event','trim','');
		$student = Http::post('student','trim','');
		$content = Http::post('content','trim','');
		$attach_ids = Http::post('attach_ids','trim','');
		$vote = Http::post('vote','int',0);
		$filterFunc = create_function('$v', 'return  is_numeric($v);');
		$student = array_unique(explode(',',$student));
		$studentArray = array_filter($student, $filterFunc);
		$student = implode(',',$studentArray);
		if(!$student && !$content){
			out(0, '参数错误');
		}
		if($vote && $event){
			out(0, 'vote,event只能有1个');
		}
		//是否老师
		$teacherInfo = load_model('teacher')->getRow(array('user'=>$this->uid));
		if(!$teacherInfo){
			out(0, '没有老师档案');
		}
		//是否老师学生
        /*
		$studentInfo = load_model('teacher_student')->getAll("teacher=$this->uid and student in ($student)");
		if(!$studentInfo || count($studentArray) != count($studentInfo)){
			out(0, 'student存在不是老师的学生');
		}
        */
		if($event){
			//课程是否存在
			if(strpos($event, '#'))
			{
				list($pid, $length) = explode("#", $event);				
				$eventInfo = load_model('event')->rec_create($pid, $length);				
			}else{
				$eventInfo = load_model('event')->getRow($event);				
			}		
			if(!$eventInfo){
				out(0, '课程不存在');
			}
			$event = $eventInfo['id'];
			//老师是否有发送通知权限
			$priv = load_model('teacher_course')->getColumn(array('event' => $event, 'teacher' => $this->uid), 'priv');	
			if(empty($priv[0]) || !($priv[0] & 8)){
				out(0, '无权限');
			}
			//学生是否有该课程
			$studentCourse = load_model('student_course')->getColumn("event = $event and student in ($student)");	
			if(!$studentCourse || count($studentArray) != count($studentCourse)){
				out(0, 'student存在不是该课程的学生');
			}
		}
		$attach_ids = array();
		//检查问卷
		if($vote){
			//问卷是否存在
			$voteInfo = load_model('vote')->getRow($vote);
			if(!$voteInfo){
				out(0, '问卷不存在');
			}
		}
		$id = load_model('notify')->create($this->uid,1,$event,$studentArray,array(),$content,array(),$vote);
		if(!$id){
			out(0, $vote ? '问卷发送失败':'通知发送失败');
		}
		//推送
		$res = logs('db')->add('notify', _hash(Http::query()), $this->get_logs(array(
			'target'=>$studentArray,																				
			'source' => array('notifyId'=>$id),	
			'type'=>2			
		)));
		out(1, $vote ? '问卷发送成功':'通知发送成功',array('id'=>$id,'event'=>$event));
	}
	
	/**
	 * structdef.xml
	 * 	SSaction     delete
	 *  SSdesc       老师删除通知
	 *  SSpargam 
	 * 		id  int  通知id(必须)
	 *  SSreturn 
	 *  SSend
	 */
	public function delete()
	{
		$id = Http::post('id','int',0);
		if(!$id){
			out(0, '参数错误');
		}
		if(!load_model('notify')->delete("id = $id and creator = $this->uid",true)){
			out(0, '通知删除失败');
		}
		out(1, '通知删除成功');
	}
}