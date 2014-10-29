<?php
class Comment_model Extends Model
{
	protected $_table = 't_comment';
	protected $_key = 'id';

	private $detail = Null;
	private $simple = Null;
	
	public function __construct(){
		parent::__construct();
	}
	
	public function getRow($param=array(), $out=false, $field='*', $order='')
	{
		$result = parent::getRow($param, $out, $field, $order);
		$tmp = array();
		if($out && $result)
		{
			if(strpos($result['student'], ","))
			{
				$students = explode(",", str_replace(array(']', '['), '', $result['student']));				
				foreach($students as $student)
				{
					$tmp[] = load_model('student')->getRow($student, true, 'id,nickname,name,avatar');
				}				
			}else if($result['student']){
				$tmp[] = load_model('student')->getRow($result['student'], true, 'id,name,nickname,avatar');			
			}
			$result['student'] = $tmp;
			$result['teacher'] = load_model('user')->getRow($result['teacher'], true, 'id,nickname,firstname,lastname,hulaid,avatar');			
		}		
		return $result;
	}
}
