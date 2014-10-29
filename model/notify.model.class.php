<?php
// 通知
class Notify_model Extends Model
{
	protected $_db = NULL;
	protected $_table = 't_notify';
	protected $_key = 'id';
	protected $_table_user = 't_user';
		
	public function __construct(){
		parent::__construct();	
	}
	
	/**
	 * 创建课程通知
	 */
	public function create($creator=0,$type=1,$event=0,$student=array(),$teacher = array(),$content='',$attachs=array(),$vote=0,$school=0,$receipt=0){
		if(!$creator ||  (!$student && !$teacher))  return false;
		$data = array(
			'creator'=>$creator,
			'type'=>$type,
			'event'=>$event,
			'create_time'=>time(),
			'student'=>json_encode($student),
			'teacher'=>json_encode($teacher),
			'content'=>$content,
			'attachs'=>json_encode($attachs),
			'vote'=>$vote,
			'school'=>$school,
			'receipt'=>$receipt,
		);
		return $this->insert($data);
	}
	
	/**
	 * 获取列表
	 */
	public function getList($character,$creator,$event=0,$student=0,$offset=0,$pagesize=20){
		$sql = "select * from $this->_table where 1";
		if($character == "teacher"){
			$query = "(creator = $creator or `teacher` like '%\"$creator\"%')";
		}
		if($event){
			$sql .= " and `event` = $event";
		}
		if($student){
			$sql .= " and student like '%\"$student\"%'";
		}
		$sql .= " order by create_time desc limit $offset,$pagesize";
		$list = db()->fetchAll($sql);
		return $list;
	}
}
