<?php
/**
 * msgtype
 * SSdesc  问卷模块
 * SSend
 */
class Vote_Api extends Api
{
	
	public function __construct(){
		parent::_init();
	}


	public function index(){
		
	}

	/**
	 * structdef.xml
	 * 	SSaction     info
	 *  SSdesc       问卷信息
	 *  SSpargam 
	 * 		id  int  问卷id
	 *  	character    string 用户身份(user,student,teacher)
	 * 		student int 学生id
	 *  SSreturn 
	 * 		id  int   问卷id
	 * 		title  varchar  标题
	 * 		multi  int 类型(1,单选，2多选)
	 * 		count  int 投票总数
	 * 		school  int  机构id
	 * 		creator  singleArray 创建者用户  user
	 * 		start_time  varchar  开始时间
	 * 		end_time  varchar  结束时间
	 * 		status int  状态(0关闭，1开启)
	 * 		voted  int  是否已经投票(0否1是)
	 * 		option array  选项 option
	 * 		record  array 问卷统计 record
	 *  SSreturn_array_user
	 * 		id  int   用户id
	 * 		nickname  varchar 昵称
	 * 		avatar int  头像更新时间
	 *  SSreturn_array_end_user
	 *  SSreturn_array_option
	 * 		id  int   选项id
	 * 		vote  int 问卷id
	 * 		title varchar 选项标题
	 * 		sort int 排序
	 *  SSreturn_array_end_option
	 *  SSreturn_array_record
	 * 		option  int   选项id
	 * 		num  int   数量
	 *  SSreturn_array_end_record
	 *  SSend
	 */
	public function info()
	{
		$id = Http::post('id','int',0);
		if(!$id){
			out(0, '参数错误');
		}
		$character = Http::post('character','string','');
		if(!$character || !in_array($character,array('user','student','teacher'))){
			out(0, '参数character错误');
		}
		$student = Http::post('student','int',0);
		if(!$student && $character == "student"){
			out(0, '参数student错误');
		}
		$vote = load_model('vote')->getRow(array('id'=>$id));
		if(!$vote){
			out(0, '问卷不存在');
		}
		$vote['voted'] = array();
		$options = load_model('vote_record')->getColumn(array('vote'=>$id,'user'=>$this->uid,'student'=>$student,'character'=>$character),'option');
		if($options){
			$vote['voted'] = $options;
		}
		$vote['start_time'] = date('Y-m-d H:i:s',$vote['start_time']);
		$vote['end_time'] = date('Y-m-d H:i:s',$vote['end_time']);
		$vote['create_time'] = date('Y-m-d H:i:s',$vote['create_time']);
		$vote['creator'] = load_model('user')->getRow($vote['creator'],false,'id,nickname,avatar');
		$vote['option'] = load_model('vote_option')->getAll(array('vote'=>$id),'','sort');
		$vote['record'] = load_model('vote_record')->getRecord($id);
		if($vote['school']){
			$vote['school'] = load_model('school')->getRow($vote['school'],false,'id,name,code,pid,type,avatar');
		}else{
			$vote['school'] = array();
		}
		out(1, '',$vote);
	}
	
	/**
	 * structdef.xml
	 * 	SSaction     getList
	 *  SSdesc       问卷列表
	 *  SSpargam 
	 * 		page  int  页码
	 *  SSreturn 
	 * 		page  array  页码数组 page
	 * 		votes  array  通知数组 votes 
	 *  SSreturn_array_page
	 * 		page  int  当前页码
	 * 		total int  总记录数
	 * 		size  int  每页显示
	 * 		pages int  总页数
	 *  SSreturn_array_end_page
	 *  SSreturn_array_votes
	 * 		id  int   问卷id
	 * 		title  varchar  标题
	 * 		multi  int 类型(1,单选，2多选)
	 * 		count  int 投票总数
	 * 		school  int  机构id
	 * 		creator  singleArray 创建者用户  user
	 * 		start_time  varchar  开始时间
	 * 		end_time  varchar  结束时间
	 * 		status int  状态(0关闭，1开启)
	 * 		option array  选项 option
	 *  SSreturn_array_end_votes
	 *  SSreturn_array_votes_user
	 * 		id  int   用户id
	 * 		nickname  varchar 昵称
	 * 		avatar int  头像更新时间
	 *  SSreturn_array_end_votes_user
	 *  SSreturn_array_votes_option
	 * 		id  int   选项id
	 * 		vote  int 问卷id
	 * 		title varchar 选项标题
	 * 		sort int 排序
	 *  SSreturn_array_end_votes_option
	 *  SSend
	 */
	public function getList()
	{
		$page = Http::post('page','int',0);
		$page = !$page || $page <= 0 ? 1 : $page;
		$total = load_model('vote')->getRow(array('creator'=>$this->uid),false,'count(1) as num');
		$total = $total['num'];
		$pagesize = 20;
		$pages = ceil($total/$pagesize);
		$pages = $pages <= 0 ? 1 : $pages;
		$offset = ($page-1)*$pagesize;
		
		$votes = load_model('vote')->getList($this->uid,$offset,$pagesize);
		if($votes){
			foreach($votes as &$vote){
				$vote['start_time'] = date('Y-m-d H:i:s',$vote['start_time']);
				$vote['end_time'] = date('Y-m-d H:i:s',$vote['end_time']);
				$vote['create_time'] = date('Y-m-d H:i:s',$vote['create_time']);
				$vote['creator'] = load_model('user')->getRow($vote['creator'],false,'id,nickname,avatar');
				$vote['option'] = load_model('vote_option')->getAll(array('vote'=>$vote['id']),'','sort');
			}
		}
		
		out(1, '', array('page'=>array('page'=>$page,'total'=>$total,'size'=>$pagesize,'pages'=>$pages), 'votes'=>$votes));
	}
	
	/**
	 * structdef.xml
	 * 	SSaction     add
	 *  SSdesc       发起问卷
	 *  SSpargam 
	 * 		title  varchar  问卷标题
	 * 		multi  int 类型(1,单选，2多选) 
	 * 		start_date  varchar  开始时间（Y-m-d H:i）
	 * 		end_date  varchar  结束时间（Y-m-d H:i）
	 * 		option  varchar  选项 '#!&'分隔
	 *  SSreturn 
	 * 		id  int  问卷id
	 *  SSend
	 */
	public function add()
	{
		$title = Http::post('title','trim','');
		if(!$title){
			out(0, '参数title错误');
		}
		$multi = Http::post('multi','int',0);
		if(!$multi || !in_array($multi,array(1,2))){
			out(0, '参数multi错误');
		}
		$start_date = Http::post('start_date', 'date','','Y-m-d H:i');
		if(!$start_date){
			out(0, '参数start_date(Y-m-d H:i)错误');
		}
		$end_date = Http::post('end_date', 'date','','Y-m-d H:i');
		if(!$end_date){
			out(0, '参数end_date(Y-m-d H:i)错误');
		}
		if($start_date >= $end_date){
			out(0, '参数end_date(Y-m-d H:i)必须大于参数start_date(Y-m-d H:i)');
		}
		$option = Http::post('option', 'trim','');
		if(!$option){
			out(0, '参数option错误');
		}
		$option = explode('#!&',$option);
		$option = array_map('trim',$option);
		if(!$option){
			out(0, '参数option错误');
		}
		db()->begin();
		try{
			$data = array(
				'title'=>$title,
				'multi'=>$multi,
				'creator'=>$this->uid,
				'start_time'=>strtotime($start_date),
				'end_time'=>strtotime($end_date),
				'create_time'=>time()
			);
			$id = load_model('vote')->insert($data);
			if(!$id) throw new Exception('失败！');
			foreach($option as $key=>$_option){
				$optionData = array(
					'title'=>$_option,
					'vote'=>$id,
					'sort'=>$key
				);
				if(!load_model('vote_option')->insert($optionData)) throw new Exception('失败！');
			}
			db()->commit();
			out(1, '',array('id'=>$id));
		}catch(Exception $e)
		{
			db()->rollback();
			Out(0, $e->getMessage());	
			return false;
		}
	}
	
	/**
	 * structdef.xml
	 * 	SSaction     update
	 *  SSdesc       修改问卷
	 *  SSpargam 
	 * 		id    int 问卷id
	 * 		title  varchar  问卷标题
	 * 		multi  int 类型(1,单选，2多选) 
	 * 		start_date  varchar  开始时间（Y-m-d H:i）
	 * 		end_date  varchar  结束时间（Y-m-d H:i）
	 * 		option  array  选项 []
	 *  SSreturn 
	 * 		id  int  问卷id
	 *  SSend
	 */
	public function update()
	{
		$id = Http::post('id','int',0);
		if(!$id){
			out(0, '参数id错误');
		}
		$vote = load_model('vote')->getRow(array('id'=>$id,'creator'=>$this->uid));
		if(!$vote){
			out(0, '问卷不存在或者不是您的');
		}
		$updateData = array();
		$title = Http::post('title','trim','');
		if($title && $title != $vote['title']){
			$updateData['title'] = $title;
		}
		$multi = Http::post('multi','int',0);
		if($multi && in_array($multi,array(1,2)) && $multi != $vote['multi']){
			$updateData['multi'] = $multi;
		}
		$start_date = Http::post('start_date', 'date','','Y-m-d H:i');
		$start_time = $vote['start_time'];
		if($start_date){
			$start_time = strtotime($start_date);
			if($start_time != $vote['start_time']){
				$updateData['start_time'] = $start_time;
			}
		}
		$end_date = Http::post('end_date', 'date','','Y-m-d H:i');
		$end_time = $vote['end_time'];
		if($end_date){
			$end_time = strtotime($end_date);
			if($end_time != $vote['end_time']){
				$updateData['end_time'] = $end_time;
			}
		}
		if($start_time >= $end_time){
			out(0, '参数end_date(Y-m-d H:i)必须大于参数start_date(Y-m-d H:i)');
		}
		$option = Http::post('option', 'trim','');
		if(!$option){
			out(0, '参数option错误');
		}
		$option = explode('#!&',$option);
		$option = array_map('trim',$option);
		if(!$option){
			out(0, '参数option错误');
		}
		db()->begin();
		try{
			
			if(!load_model('vote')->update($updateData,array('id'=>$id,'creator'=>$this->uid)))  throw new Exception('失败！');
			if(!load_model('vote_option')->delete(array('vote'=>$id),true))   throw new Exception('失败！');
			foreach($option as $key=>$_option){
				$optionData = array(
					'title'=>$_option,
					'vote'=>$id,
					'sort'=>$key
				);
				if(!load_model('vote_option')->insert($optionData)) throw new Exception('失败！');
			}
			db()->commit();
			out(1, '更新成功',array('id'=>$id));
		}catch(Exception $e)
		{
			db()->rollback();
			Out(0, $e->getMessage());	
			return false;
		}
	}
	
	/**
	 * structdef.xml
	 * 	SSaction     doVote
	 *  SSdesc       做问卷
	 *  SSpargam 
	 * 		vote    int 问卷id
	 * 		character    string 用户身份(user,student,teacher)
	 * 		student int 学生id
	 * 		option  varchar  选项,','分隔
	 *  SSreturn 
	 *  SSend
	 */
	public function doVote(){
		$voteId = Http::post('vote','int',0);
		if(!$voteId){
			out(0, '参数vote错误');
		}
		$character = Http::post('character','string','');
		if(!$character || !in_array($character,array('user','student','teacher'))){
			out(0, '参数character错误');
		}
		$student = Http::post('student','int',0);
		if(!$student && $character == "student"){
			out(0, '参数student错误');
		}
		$vote = load_model('vote')->getRow(array('id'=>$voteId));
		if(!$vote){
			out(0, '问卷不存在');
		}
		if($vote['end_time'] < time() || $vote['start_time'] > time()){
			out(0, '问卷已结束或未开始，无法投票！');
		}
		$option = Http::post('option','string','');
		if(!$option){
			out(0, '参数option错误');
		}
		$option = array_unique(explode(',',$option));
		$filterFunc = create_function('$v', 'return  is_numeric($v);');
		$optionArray = array_filter($option, $filterFunc);
		$option = implode(',',$optionArray);
		if(!$option)
		{
			out(0, '参数错误 option');
		}
		if(count($optionArray) > 1 && $vote['multi'] == 1){
			out(0, '只能单选');
		}
		$optionInfo = load_model('vote_option')->getAll("vote=$voteId and id in ($option)");
		if(!$optionInfo || count($optionArray) != count($optionInfo)){
			out(0, '选项错误');
		}
		if(load_model('vote_record')->getRow(array('vote'=>$voteId,'user'=>$this->uid,'student'=>$student,'character'=>$character))){
			out(0, '您已经投过票了！');
		}
		db()->begin();
		try{	
			if(!load_model('vote')->increment('count',"id=$voteId"))  throw new Exception('失败！');
			$data = array(
				'vote'=>$voteId,
				'user'=>$this->uid,
				'student'=>$student,
				'character'=>$character,
				'create_time'=>date('Y-m-d H:i:s'),
				'ip'=>Http::ip()
			);
			foreach($optionArray as $key=>$_option){
				$data['option'] = $_option;
				if(!load_model('vote_record')->insert($data)) throw new Exception('您已经投过票了！');
			}
			db()->commit();
			out(1, '成功');
		}catch(Exception $e)
		{
			db()->rollback();
			Out(0, $e->getMessage());	
			return false;
		}
	}
	
}