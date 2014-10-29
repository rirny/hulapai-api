<?php
/**
 * 呼啦圈
 */
class Blog_Api extends Api
{
	public function __construct(){
		parent::_init();
	}

	/**
	 * 广场
	 */
	public function index(){
	}
	
	
	/**
	 * 我的呼啦圈首页
	 */
	public function space()
	{
		$page = Http::post('page','int',0);
		$page = !$page || $page <= 0 ? 1 : $page;
		//我的好友包括自己
		$uids = load_model('friend')->getFriendUidStr($this->uid,0,0);
		$uids = $uids ? $uids.','.$this->uid : $this->uid;
		$_Blog = load_model('blog');
		$total = $_Blog->getBlogsNum($uids);
		$pagesize = 10;
		$pages = ceil($total/$pagesize);
		$pages = $pages <= 0 ? 1 : $pages;
		$offset = ($page-1)*$pagesize;
		
		$blogs = $_Blog->getBlogs($uids,$offset,$pagesize);
		if($blogs){
			$_Blog_Comment = load_model('blog_comment');
			$_Blog_Digg = load_model('blog_digg');
			foreach($blogs as &$blog)
			{
				//评论信息
				$blog['comment'] = $_Blog_Comment->getComments($uids,$blog['blog_id']);
				//赞信息
				$blog['digg'] = $_Blog_Digg->getRow(array('uid'=>$this->uid,'blog_id'=>$blog['blog_id'])) ? 1 : 0;
			}
		}
		out(1, '', array('page'=>array('page'=>$page,'total'=>$total,'size'=>$pagesize,'pages'=>$pages), 'blogs'=>$blogs));
	}
	
	
	/**
	 * 用户呼啦圈首页
	 */
	public function user()
	{
		$uid = Http::post('uid','int',0);
		$uid = !$uid ? $this->uid : $uid;
		$page = Http::post('page','int',0);
		$page = !$page || $page <= 0 ? 1 : $page;
		$_Blog = load_model('blog');
		$total = $_Blog->getBlogsNum($uid);
		$pagesize = 10;
		$pages = ceil($total/$pagesize);
		$pages = $pages <= 0 ? 1 : $pages;
		$offset = ($page-1)*$pagesize;

		$blogs = $_Blog->getBlogs($uid,$offset,$pagesize);
		if($blogs){
			$_Blog_Comment = load_model('blog_comment');
			$_Blog_Digg = load_model('blog_digg');
			//我的好友包括自己
			$uids = load_model('friend')->getFriendUidStr($uid,0,0);
			$uids = $uids ? $uids.','.$uid : $uid;
			foreach($blogs as &$blog)
			{
				//评论信息
				$blog['comment'] = $_Blog_Comment->getComments($uids,$blog['blog_id']);
				//赞信息
				$blog['digg'] = $_Blog_Digg->getRow(array('uid'=>$this->uid,'blog_id'=>$blog['blog_id'])) ? 1 : 0;
			}
		}
		//data
		$friend = 1;
		if($uid != $this->uid)
		{
			//是否好友
			if(!load_model('friend')->is_friend($this->uid, $uid))
			{
				$friend = 0;
			}
		}
		out(1, '', array('page'=>array('page'=>$page,'total'=>$total,'size'=>$pagesize,'pages'=>$pages), 'blogs'=>$blogs, 'friend'=>$friend));
	}
	
	
	/**
	 * 获取动态的详细信息
	 */
	public function info()
	{
		$blog_id = Http::post('blog_id','int',0);
		if(!$blog_id)
		{
			out(0, '参数错误 blog_id');
		}
		$blog = load_model('blog')->getBlog($blog_id);
		if(!$blog)
		{
			out(0, '没有此数据');
		}
		//我的好友包括自己
		$uids = load_model('friend')->getFriendUidStr($this->uid,0,0);
		$uids = $uids ? $uids.','.$this->uid : $this->uid;
		//评论信息
		$blog['comment'] = load_model('blog_comment')->getComments($uids,$blog_id);
		//赞信息
		$blog['digg'] = load_model('blog_digg')->getRow(array('uid'=>$this->uid,'blog_id'=>$blog_id)) ? 1 : 0;
		out(1, '', array('blog'=>$blog));
	}
	
	/**
	 * 发表动态
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
			out(0, '参数错误');
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
		//发表动态
		$blog_info = $this->postBlog($this->uid,$type,$content,$attach_ids,$attachs);
		if(!$blog_info){
			out(0, '发布失败');
		}
		out(1, '发布成功',array('blog'=>$blog_info));
	}
	
	/**
	 * 转发动态
	 * blog_id  转发动态id
	 * curid  转发当前动态id
	 * content 内容
	 */
	public function reAdd()
	{
		import('filter');
		$content = Http::post('content', 'trim');
		$content = isset($content) ? Filter::filter_keyword(Filter::safe_html($_POST['content'])) : '';
		$blog_id = Http::post('blog_id','int',0);
		$curid = Http::post('curid','int',0);
		$attach_ids = '';
		$attachs = array();
		$type = 'post';
		if(!$blog_id || !$content || !$curid)
		{
			out(0, '参数错误');
		}
		$blog = load_model('blog')->getRow(array('blog_id'=>$blog_id));
		if(!$blog)
		{
			out(0, '动态不存在');
		}
		if($blog_id != $curid){
			$content = '//@'.$this->name.'：'.$blog['content'].$content;
		}
		//转发动态
		$new_blog_info = $this->postBlog($this->uid,$type,$content,'',array(),1,$blog_id);
		if(!$new_blog_info){
			out(0, '转发失败');
		}
		out(1, '转发成功',array('blog'=>$new_blog_info));
	}
	
	
	private function postBlog($uid,$type='post',$content='',$attach_ids='',$attachs=array(),$is_repost=0,$source_id=0){
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
			$blog = array(
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
			$_Blog = load_model('blog');
			//是转发,转发+1
			if($is_repost && $source_id){
				 $source_info = $_Blog->getBlog($source_id);
				 $source_info['source_info'] = array();
				 unset($source_info['comment_count']);
				 unset($source_info['repost_count']);
				 unset($source_info['digg_count']);
				 $blog['source_info'] = serialize($source_info);
				 if(!$_Blog->increment('repost_count', array('blog_id'=>$source_id), 1)) throw new Exception('失败！');
			}
			//入主表
			$blog_id = $_Blog->insert($blog);
			if(!$blog_id) throw new Exception('失败！');
			//推送
			$uids = load_model('friend')->getFriendUidStr($this->uid,0,0);
			if($uids){
				$to = explode(",", $uids);
				push('db')->add('H_PUSH',array(
					'app' => $this->app,'act' => $this->act,'from' =>$this->uid,'to'=>$to,'type' => 1,'ext'=> array('blog_id'=>$blog_id)
				));	
			}
			db()->commit();
			$data = $_Blog->getBlog($blog_id);
			$data['comment'] = array();
			$data['digg'] = 0;
			return $data;
		}catch(Exception $e)
		{
			db()->rollback();
			Out(0, $e->getMessage());	
			return false;
		}
	}
	/**
	 * 删除动态
	 */
	public function delete()
	{
		$blog_id = Http::post('blog_id','int',0);
		if(!$blog_id)
		{
			out(0, '参数错误');
		}
		if(!load_model('blog')->delete(array('blog_id'=>$blog_id,'uid'=>$this->uid),true)){
			out(0, '删除失败');
		}
		out(1, '删除成功');
	}
}