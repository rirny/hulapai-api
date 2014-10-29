<?php
// 用户 - 学生

class User_student_model Extends Model
{
	protected $_table = 't_user_student';
	protected $_key = 'id';

	private $detail = Null;
	private $simple = Null;
	
	public function __construct(){
		parent::__construct();
	}

	// 获取当前用户下的学生
	public function get_user_student($uid, $out=false)
	{
		$result = array();
		if(!$uid) return $result;		
		$result = db()->fetchAll("select s.*,r.relation,r.id rid from " . $this->_table . " r left join t_student s on r.`student`=s.id where s.`status`=0 And r.`user`=" . $uid);				
		if($result && $out){
			foreach($result as $key => $item){
				$result[$key] = $this->format($item);
			}
		}
		return $result;
	}

	public function get_relation($uid, $student)
	{
		if(!$uid || !$student) return false;
		$res = db()->fetchOne("select relation from ". $this->_table . " where student=" . $student . " And `user`=" . $uid);
		if($res) return $res;
		return false;
	}

	public function get_parents($student, $out=false)
	{
		$result = array();
		if(!$student) return $result;		
		$result = db()->fetchAll("select `relation`,`user` from ". $this->_table . " where student=" . $student);		
		if($result && $out){
			foreach($result as $key => $item){
				$item = $this->Format($item);
				$parent = load_model('user')->getRow($item['user'], true, 'id,hulaid,avatar,nickname,firstname,lastname');
                unset($item['user']);
				$result[$key] = array_merge($item, $parent);
			}
		}		
		return $result;
	}
}
