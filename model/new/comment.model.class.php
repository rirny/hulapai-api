<?php
/*
 * 课程模型
*/
class Comment_Model_New Extends Model_New
{
	
	protected $_db = NULL;
	protected $_table = 'comment';
	protected $_key = 'id';

	public function __Construct()
	{
		parent::__construct();
	}

	// static $attatchs = Array();
	public function formatForApp($data)
	{
		static $teachers = Array();
		static $students = Array();
		static $relations = Array();
		static $users = Array();
		static $series = Array();		
		if(!$data) return Array();		
		$tid = $data['teacher'];		
		if(empty($teachers[$tid]))
		{
			$teachers[$tid] = load_model('user', Null, true)->field('id,concat(firstname&lastname) name,avatar')->where('id', $tid, true)->Row();			
		}
		empty($teachers[$tid]) || $data['teacher'] = $teachers[$tid];
		
		$sid = $data['student'];
		if(empty($students[$sid]))
		{				
			$students[$sid] = load_model('student', Null, true)->field('id,name,avatar')->where('id', $sid, true)->Row();
		}
		empty($students[$sid]) || $data['student'] = $students[$sid];
		
		if(isset($data['character']) && $data['character'] == 'student')
		{
			$uid = $data['creator'];
			if(empty($teachers[$uid]))
			{				
				$teachers[$uid] = load_model('user', Null, true)->field('id,account,firstname,lastname,avatar')->where('id', $uid, true)->Row();
			}					
			$rid = $sid.'_'.$data['creator'];
			if(empty($relations[$rid]))
			{
				$relations[$rid] = load_model('user_student', Null, true)->field('relation')->where('student', $sid, true)->where('user', $data['creator'])->limit(1)->Column();				
			}		
			empty($teachers[$uid]) || $data['creator'] = $teachers[$uid];
			empty($relations[$rid]) || $data['student']['relation'] = $relations[$rid];
		}
		
		if(!empty($data['attach']))
		{			
			$data['attach'] = $this->get_attach($data['attach']);			
		}		
		
		if(!empty($data['sid']) && empty($data['pid']))
		{
			$eid = $data['sid'];
			if(empty($series[$eid]))
			{
				$series[$eid] = load_model('series', Null, true)->field('id,title')->where('id', $data['sid'], true)->Row();				
			}
			$data['event'] = $series[$eid];			
			$data['event']['index'] = $data['index'];
			$data['event']['date'] = date('Y-m-d', $data['index']);
			unset($data['sid'], $data['index']);
		}
		return $data;
	}

	/*
	 * 附件
	*/
	public function get_attach($id)
	{
		$result = Array();
		if(!preg_match('/[0-9,]/', $id))
		{
			return $result;
		}
		$attachs =  db()->fetchAll("select attach_id,uid,name as attach_name,size,extension,save_path,save_name from ts_attach where attach_id in ($id)");
		
		foreach($attachs as $item)
		{
			$root = Config::get('path', 'upload');
			$file = substr($item['save_name'],0,-4);
			$attach_url_size = getimagesize($root .'/' . $item['save_path'].$item['save_name']);
			$attach_small_size = getimagesize($root .'/' . $item['save_path']. $file . "_small.jpg");
			$attach_middle_size = getimagesize($root .'/' . $item['save_path']. $file . "_middle.jpg");            
			$result[] = array(
				'attach_id' => $item['attach_id'],
				'attach_url'=> $item['save_path'].$item['save_name'],
				'attach_url_size'=>$attach_url_size[0].'_'.$attach_url_size[1],
				'attach_small' => $item['save_path']. $file . "_small.jpg",
				'attach_small_size'=>$attach_small_size[0].'_'.$attach_small_size[1],
				'attach_middle'=> $item['save_path']. $file . "_middle.jpg",
				'attach_middle_size'=>$attach_middle_size[0].'_'.$attach_middle_size[1],
				'domain' => 'HOST_IMAGE'
			);
		}
		return $result;
	}


}