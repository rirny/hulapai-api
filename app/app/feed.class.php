<?php
/**
 * 微博
 */
class Feed_Api extends Api
{
	public function __construct(){
		parent::_init();
	}

	/**
	 * 广场
	 */
	public function index(){
		$page = Http::post('page','int',0);
		$uids = Http::post('uids','string','');
		$page = !$page || $page <= 0 ? 1 : $page;
		$_Feed = load_model('feed');
		$total = $_Feed->getFeedsNum($uids);
		$pagesize = 10;
		$pages = ceil($total/$pagesize);
		$pages = $pages <= 0 ? 1 : $pages;
		$offset = ($page-1)*$pagesize;
		
		$feeds = $_Feed->getFeeds($uids,$offset,$pagesize);
		if($feeds){
			$_Feed_Comment = load_model('feed_comment');
			$_Feed_User_Collection = load_model('feed_user_collection');
			foreach($feeds as &$feed)
			{
				//评论信息
				$feed['comment'] = $_Feed_Comment->getComments($feed['feed_id']);
				//收藏信息
				$feed['collect'] = $_Feed_User_Collection->getRow(array('uid'=>$this->uid,'feed_id'=>$feed['feed_id'])) ? 1 : 0;
			}
		}
		//data
		$data = array(
			'feed_count'=>0,
			'feed_following_count'=>0,
			'feed_follower_count'=>0,
			'feed_collect_count'=>0,
		);
		$data_keys = array_keys($data);
		//动态值，如关注数等
		$user_data = load_model('feed_user_data')->getAll(array('uid'=>$this->uid));
		if($user_data){
			foreach($user_data as $d)
			{
				if(in_array($d['key'],$data_keys)){
					$data[$d['key']] = $d['value'];
				}
			}
		}
		$data['feed_collect_count'] = load_model('feed_user_collection')->getUserCollectionNum($this->uid);
		out(1, '', array('page'=>array('page'=>$page,'total'=>$total,'size'=>$pagesize,'pages'=>$pages), 'feeds'=>$feeds, 'user_data'=>$data));
	}
	
	
	/**
	 * 我的微博首页
	 */
	public function space()
	{
		$page = Http::post('page','int',0);
		$page = !$page || $page <= 0 ? 1 : $page;
		
		//我关注的人包括自己
		//$uids = load_model('feed_user_follow')->getUserFollowingUidStr($this->uid,0,0);
		$uids = $uids ? $uids.','.$this->uid : $this->uid;
		$_Feed = load_model('feed');
		$total = $_Feed->getFeedsNum($uids);
		$pagesize = 10;
		$pages = ceil($total/$pagesize);
		$pages = $pages <= 0 ? 1 : $pages;
		$offset = ($page-1)*$pagesize;
		
		$feeds = $_Feed->getFeeds($uids,$offset,$pagesize);
		if($feeds){
			$_Feed_Comment = load_model('feed_comment');
			$_Feed_User_Collection = load_model('feed_user_collection');
			foreach($feeds as &$feed)
			{
				//评论信息
				$feed['comment'] = $_Feed_Comment->getComments($feed['feed_id']);
				//收藏信息
				$feed['collect'] = $_Feed_User_Collection->getRow(array('uid'=>$this->uid,'feed_id'=>$feed['feed_id'])) ? 1 : 0;
			}
		}
		//data
		$data = array(
			'feed_count'=>0,
			'feed_following_count'=>0,
			'feed_follower_count'=>0,
			'feed_collect_count'=>0,
		);
		$data_keys = array_keys($data);
		//动态值，如关注数等
		$user_data = load_model('feed_user_data')->getAll(array('uid'=>$this->uid));
		if($user_data){
			foreach($user_data as $d)
			{
				if(in_array($d['key'],$data_keys)){
					$data[$d['key']] = $d['value'];
				}
			}
		}
		$data['feed_collect_count'] = load_model('feed_user_collection')->getUserCollectionNum($this->uid);
		out(1, '', array('page'=>array('page'=>$page,'total'=>$total,'size'=>$pagesize,'pages'=>$pages), 'feeds'=>$feeds, 'user_data'=>$data));
	}
	
	
	/**
	 * 用户微博首页
	 */
	public function user()
	{
		$uid = Http::post('uid','int',0);
		$uid = !$uid ? $this->uid : $uid;
		$page = Http::post('page','int',0);
		$page = !$page || $page <= 0 ? 1 : $page;
		$_Feed = load_model('feed');
		$total = $_Feed->getFeedsNum($uid);
		$pagesize = 10;
		$pages = ceil($total/$pagesize);
		$pages = $pages <= 0 ? 1 : $pages;
		$offset = ($page-1)*$pagesize;

		$feeds = $_Feed->getFeeds($uid,$offset,$pagesize);
		if($feeds){
			$_Feed_Comment = load_model('feed_comment');
			$_Feed_User_Collection = load_model('feed_user_collection');
			foreach($feeds as &$feed)
			{
				//评论信息
				$feed['comment'] = $_Feed_Comment->getComments($feed['feed_id']);
				//收藏信息
				$feed['collect'] = $_Feed_User_Collection->getRow(array('uid'=>$this->uid,'feed_id'=>$feed['feed_id'])) ? 1 : 0;
			}
		}
		//data
		$following = 0;
		$data = array(
			'feed_count'=>0,
			'feed_following_count'=>0,
			'feed_follower_count'=>0,
			'feed_collect_count'=>0,
		);
		$data_keys = array_keys($data);
		//动态值，如关注数等
		$user_data = load_model('feed_user_data')->getAll(array('uid'=>$uid));
		if($user_data){
			foreach($user_data as $d)
			{
				if(in_array($d['key'],$data_keys)){
					$data[$d['key']] = $d['value'];
				}
			}
		}
		$data['feed_collect_count'] = load_model('feed_user_collection')->getUserCollectionNum($uid);
		if($uid != $this->uid)
		{
			//是否关注过
			if(load_model('feed_user_follow')->getRow(array('uid'=>$this->uid,'fid'=>$uid)))
			{
				$following = 1;
			}
		}
		out(1, '', array('page'=>array('page'=>$page,'total'=>$total,'size'=>$pagesize,'pages'=>$pages), 'feeds'=>$feeds, 'user_data'=>$data, 'following'=>$following));
	}
	
	
	/**
	 * 获取微博的详细信息
	 */
	public function info()
	{
		$feed_id = Http::post('feed_id','int',0);
		if(!$feed_id)
		{
			out(0, '参数错误 feed_id');
		}
		$feed = load_model('feed')->getFeed($feed_id);
		if(!$feed)
		{
			out(0, '没有此数据');
		}
		//评论信息
		$feed['comment'] = load_model('feed_comment')->getComments($feed_id);
		//收藏信息
		$feed['collect'] = load_model('feed_user_collection')->getRow(array('uid'=>$this->uid,'feed_id'=>$feed_id)) ? 1 : 0;
		out(1, '', array('feed'=>$feed));
	}
	
	/**
	 * 发表微博
	 * content  内容
	 * attach_ids  图片id,','分隔
	 */
	public function add()
	{
		import('filter');
		$content = Http::post('content', 'trim');
		$content = isset($content) ? Filter::filter_keyword(Filter::safe_html($_POST['content'])) : '';
		$attach_ids = Http::post('attach_ids', 'trim','');
		$attachs = array();
		$type = 'post';
		
		if(!$content && !$attach_ids)
		{
			out(0, '内容或图片不能为空！');
		}
		if($attach_ids)
		{
			$attach_ids = array_unique(explode(',',$attach_ids));
			$filterFunc = create_function('$v', 'return  is_numeric($v);');
			$attach_ids = array_filter($attach_ids, $filterFunc);
			$attach_ids = implode(',',$attach_ids);
			if($attach_ids)
			{
				$attach_ids_arr = array();
				$_Attach = load_model('attach');
				$att = $_Attach->getAttachs($attach_ids);
				if($att){
					$path = Config::get('path', 'upload', null, null);	
					foreach($att as $key=>$a)
					{
						$attach_ids_arr[] = $a['attach_id'];
						if($a['uid'] != $this->uid)
						{
							out(0, '非法图片数据，检查参数attach_ids');
						}else{
			                $imgInfo = pathinfo($a['save_name']);
			                $imagePath = $path.'/'.$a['save_path'];	
			                $attach_url_size = getimagesize($imagePath.$a['save_name']);
							$attach_small_size = getimagesize($imagePath.$imgInfo['filename'].'_small.'.$imgInfo['extension']);
							$attach_middle_size = getimagesize($imagePath.$imgInfo['filename'].'_middle.'.$imgInfo['extension']);            
							$attachs[$key]['attach_id'] = $a['attach_id'];
							$attachs[$key]['attach_url'] = $a['save_path'].$a['save_name'];
							$attachs[$key]['attach_url_size'] = $attach_url_size[0].'_'.$attach_url_size[1];
							$attachs[$key]['attach_small'] = $a['save_path'].$imgInfo['filename'].'_small.'.$imgInfo['extension'];
							$attachs[$key]['attach_small_size'] = $attach_small_size[0].'_'.$attach_small_size[1];
							$attachs[$key]['attach_middle'] = $a['save_path'].$imgInfo['filename'].'_middle.'.$imgInfo['extension'];
							$attachs[$key]['attach_middle_size'] = $attach_middle_size[0].'_'.$attach_middle_size[1];
							$attachs[$key]['domain'] = 'HOST_IMAGE';
						}
					}
					$type = 'postimage';
				}
				$attach_ids = implode(',',$attach_ids_arr);
			}
		}
		//发表微博
		$feed_info = $this->postFeed($this->uid,$type,$content,$attach_ids,$attachs);
		if(!$feed_info){
			out(0, '微博发布失败');
		}
		out(1, '微博发布成功',array('feed'=>$feed_info));
	}
	
	/**
	 * 转发微博
	 * feed_id  转发微博id
	 * curid  转发当前微博id
	 * content 内容
	 */
	public function reAdd()
	{
		import('filter');
		$content = Http::post('content', 'trim');
		$content = isset($content) ? Filter::filter_keyword(Filter::safe_html($_POST['content'])) : '';
		$feed_id = Http::post('feed_id','int',0);
		$curid = Http::post('curid','int',0);
		$attach_ids = '';
		$attachs = array();
		$type = 'post';
		if(!$feed_id || !$content || !$curid)
		{
			out(0, '参数错误');
		}
		$feed = load_model('feed')->getRow(array('feed_id'=>$feed_id));
		if(!$feed)
		{
			out(0, '微博不存在');
		}
		if($feed_id != $curid){
			$content = '//@'.$this->name.'：'.$feed['content'].$content;
		}
		//转发微博
		$new_feed_info = $this->postFeed($this->uid,$type,$content,'',array(),1,$feed_id);
		if(!$new_feed_info){
			out(0, '微博转发失败');
		}
		out(1, '微博转发成功',array('feed'=>$new_feed_info));
	}
	
	
	private function postFeed($uid,$type='post',$content='',$attach_ids='',$attachs=array(),$is_repost=0,$source_id=0){
		db()->begin();
		try{
			$nowTime = time();
			$device = Http::get_device();				
			if($device['src'] == 'ios')
			{
				$from = 3;
			}else if($device['src'] == 'android')
			{
				$from = 2;
			}else{
				$from = 0;
			}	
			$feed = array(
				'uid'=>$uid,
				'type'=>$type,
				'is_repost'=>$is_repost,
				'source_id'=>$source_id,
				'source_info'=>serialize(array()),
				'attach_ids'=>$attach_ids,
				'attachs'=>serialize($attachs),
				'client_ip'=>Http::ip(),
				'publish_time'=>$nowTime,
				'from'=>$from,
				'content'=>$content,
			);
			$_Feed = load_model('feed');
			$_Feed_User_Data = load_model('feed_user_data');
			//是转发,转发+1
			if($is_repost && $source_id){
				 $source_info = $_Feed->getFeed($source_id,'basic');
				 $source_info['source_info'] = array();
				 unset($source_info['comment_count']);
				 unset($source_info['repost_count']);
				 unset($source_info['collect_count']);
				 unset($source_info['digg_count']);
				 $feed['source_info'] = serialize($source_info);
				 if(!$_Feed->increment('repost_count', array('feed_id'=>$source_id), 1)) throw new Exception('失败！');
			}
			//入微博主表
			$feed_id = $_Feed->insert($feed);
			if(!$feed_id) throw new Exception('失败！');
			//更新用户微博数
			if(!$_Feed_User_Data->increment('value', array('uid'=>$uid,'key'=>'feed_count'), 1))
			{
				$ts_user_data = array(
					'uid'=>$uid,
					'key'=>'feed_count',
					'value'=>1,
					'mtime'=>$nowTime,
				);
				if(!$_Feed_User_Data->insert($ts_user_data)) throw new Exception('失败！');
			}
			//推送
			$uids = load_model('feed_user_follow')->getUserFollowingUidStr($this->uid,0,0);
			if($uids){
				$to = explode(",", $uids);
				push('db')->add('H_PUSH',array(
					'app' => $this->app,'act' => $this->act,'from' =>$this->uid,'to'=>$to,'type' => 1,'ext'=> array('feed_id'=>$feed_id)
				));	
			}
			
			db()->commit();
			$data = $_Feed->getFeed($feed_id);
			$data['comment'] = array();
			$data['collect'] = 0;
			return $data;
		}catch(Exception $e)
		{
			db()->rollback();
			Out(0, $e->getMessage());	
			return false;
		}
	}
	/**
	 * 删除微博
	 */
	public function delete()
	{
		$feed_id = Http::post('feed_id','int',0);
		if(!$feed_id)
		{
			out(0, '参数错误');
		}
		db()->begin();
		try{
			//删除微博数据表
			if(!load_model('feed')->delete(array('feed_id'=>$feed_id,'uid'=>$this->uid),true)) throw new Exception('微博删除失败！');
			//更新用户微博数
			if(!load_model('feed_user_data')->decrement('value', array('uid'=>$this->uid,'key'=>'feed_count'), 1)) throw new Exception('微博删除失败！');
			db()->commit();
			out(1, '微博删除成功');
		}catch(Exception $e)
		{
			db()->rollback();
			Out(0, $e->getMessage());	
		}
	}
	
	// 推荐
	public function recommend()
	{
		$page = Http::post('page', 'int', 1);
		$refresh = Http::post('refresh', 'int', 0);
		$result = load_model('feed_user_follow')->teacherFollow($page, $refresh, $this->uid,10);
		if(!$result) throw new Exception("没有数据！");
		Out(1, '', $result);
	}

}