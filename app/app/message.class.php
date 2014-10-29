<?php
/**
 * msgtype
 * SSdesc  消息
 * SSend
 */
class Message_Api extends Api
{
	public function __construct(){
		parent::_init();
	}

	public function index(){
		
	}
	
	/**
	 * structdef.xml
	 * 	SSaction     info
	 *  SSdesc       消息信息
	 *  SSpargam 
	 * 		id  int  通知id
	 *  SSreturn 
	 * 		id  int   消息id
	 * 		content  varchar  内容
	 * 		event	int 课程id
	 * 		from  singleArray  发送者  user
	 * 		to  singleArray 接收者  user
	 * 		student  singleArray  接受学生 student
	 * 		type	int   类型(0系统1课程2问卷)
	 * 		create_time	varchar 发送时间
	 * 		school  int  机构id
	 * 		status	int  状态(0新1已读)
	 * 		source  singelArray 来源信息  source
	 * 		attachs  array  附件信息 attachs
	 * 		pid   int  父id
	 * 		reply  int 是否可回复
	 *  SSreturn_array_user
	 * 		id  int   用户id
	 * 		nickname  varchar 昵称
	 * 		avatar int  头像更新时间
	 *  SSreturn_array_end_user
	 *  SSreturn_array_student
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
	 *  SSreturn_array_end_student
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
		if(!load_model('message')->update(array('status'=>1),array('to'=>$this->uid,'id'=>$id))){
			out(0, '消息不存在');
		}
		$message = load_model('message')->getRow(array('to'=>$this->uid,'id'=>$id));
		$message = $this->_info($message);
		out(1, '',$message);
	}
	
	
	private function _info($message){
		if($message['from']){
			$message['from'] = load_model('user')->getRow($message['from'],false,'id,nickname,firstname,lastname,avatar');
		}else{
			$message['from'] = array();
		}
		$message['teacher'] = array();
		if($message['to']){
			$message['to'] = load_model('user')->getRow($message['to'],false,'id,nickname,firstname,lastname,avatar');
			if($message['character'] == "teacher") $message['teacher'] = $message['to'];
		}else{
			$message['to'] = array();
		}
		if($message['student']){
			$message['student'] = load_model('student')->getRow(array('id' => $message['student']),false,'id,name,nickname,avatar');	
		}else{
			$message['student'] = array();
		}
		if($message['school']){
			$message['school'] = load_model('school')->getRow($message['school'],false,'id,name,code,pid,type,avatar');
		}else{
			$message['school'] = array();
		}
		$message['source'] = json_decode($message['source'],true);
		if($message['type'] == 2 && $message['source']['id']){
			$message['source'] = load_model('vote')->getRow($message['source']['id'],'id,title');
		}
		$message['attachs'] = json_decode($message['attachs'],true);
		$message['create_time'] = date('Y-m-d H:i:s',$message['create_time']);
		
		return $message;
	}
	/**
	 * structdef.xml
	 * 	SSaction     getList
	 *  SSdesc       消息列表
	 *  SSpargam 
	 * 		type  int  类型(0系统1课程2问卷) 
	 * 		page  int  页码
	 *  SSreturn 
	 * 		page  array  页码数组 page
	 * 		messages  array  通知数组 messages 
	 *  SSreturn_array_page
	 * 		page  int  当前页码
	 * 		total int  总记录数
	 * 		size  int  每页显示
	 * 		pages int  总页数
	 *  SSreturn_array_end_page
	 *  SSreturn_array_messages
	 * 		id  int   消息id
	 * 		content  varchar  内容
	 * 		event	int 课程id
	 * 		from  singleArray  发送者  user
	 * 		to  singleArray 接收者  user
	 * 		student  singleArray  接受学生 student
	 * 		type	int   类型(0系统1课程2问卷)
	 * 		create_time	varchar 发送时间
	 * 		school  int  机构id
	 * 		status	int  状态(0新1已读)
	 * 		source  singelArray 来源信息  source
	 * 		attachs  array  附件信息 attachs
	 * 		pid   int  父id
	 * 		reply  int 是否可回复
	 *  SSreturn_array_end_messages
	 *  SSreturn_array_messages_user
	 * 		id  int   用户id
	 * 		nickname  varchar 昵称
	 * 		avatar int  头像更新时间
	 *  SSreturn_array_end_messages_user
	 *  SSreturn_array_messages_student
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
	 *  SSreturn_array_end_messages_student
	 *  SSreturn_array_messages_attachs
	 * 		attach_id  int   附件id
	 * 		attach_url  varchar 附件地址
	 * 		attach_small varchar 附件地址
	 * 		attach_middle varchar 附件地址
	 * 		domain  varchar   domain
	 *  SSreturn_array_end_messages_attachs
	 *  SSend
	 */
	public function getList()
	{
		$type = Http::post('type','string', 0);
        $types = explode(",", $type);        
		if(array_diff($type,array(0,1,2))){
			out(0, '参数错误@Er.param.type');
		}
		$page = Http::post('page','int',0);
		$page = !$page || $page <= 0 ? 1 : $page;
		$total = load_model('message')->getRow(array('to'=>$this->uid,'type,in'=>$types),false,'count(1) as num');
		$total = $total['num'];
		$pagesize = 20;
		$pages = ceil($total/$pagesize);
		$pages = $pages <= 0 ? 1 : $pages;
		$offset = ($page-1)*$pagesize;
		
		$messages = load_model('message')->getList($this->uid,$types,$offset,$pagesize);
		if($messages){
			foreach($messages as &$message){
				$message = $this->_info($message);
			}
		}
		
		out(1, '', array('page'=>array('page'=>$page,'total'=>$total,'size'=>$pagesize,'pages'=>$pages), 'messages'=>$messages));
	}
	
	/**
	 * structdef.xml
	 * 	SSaction     delete
	 *  SSdesc       删除消息
	 *  SSpargam 
	 * 		id  int  消息id(必须)
	 *  SSreturn 
	 *  SSend
	 */
	public function delete()
	{
		$id = Http::post('id','int',0);
		if(!$id){
			out(0, '参数错误');
		}
		if(!load_model('message')->delete("id = $id and `to` = $this->uid",true)){
			out(0, '消息删除失败');
		}
		out(1, '消息删除成功');
	}
}