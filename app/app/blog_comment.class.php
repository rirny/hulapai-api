<?php
/**
 * 呼啦圈评论
 */
class Blog_comment_Api extends Api
{
	public function __construct(){
		parent::_init();
	}

	/** 
	 * 获取动态的评论信息
	*/
	public function getList()
	{
		$blog_id = Http::post('blog_id','int',0);
		if(!$blog_id)
		{
			out(0, '参数错误 blog_id');
		}
		$page = Http::post('page','int',0);
		$page = !$page || $page <= 0 ? 1 : $page;
		$_Blog_Comment = load_model('blog_comment');
		//我的好友包括自己
		$uids = load_model('friend')->getFriendUidStr($this->uid,0,0);
		$uids = $uids ? $uids.','.$this->uid : $this->uid;
		$total = $_Blog_Comment->getCommentsNum($uids,$blog_id);
		$pagesize = 20;
		$pages = ceil($total/$pagesize);
		$pages = $pages <= 0 ? 1 : $pages;
		$offset = ($page-1)*$pagesize;

		$comments = $_Blog_Comment->getComments($uids,$blog_id,$offset,$pagesize);
		
		out(1, '', array('comments'=>$comments, 'page'=>array('page'=>$page,'total'=>$total,'size'=>$pagesize,'pages'=>$pages)));
	}	
	
	/**
	 * 评论
	 */
	public function add()
	{
		import('filter');
		$content = Http::post('content', 'trim');
		$content = isset($content) ? Filter::filter_keyword(Filter::safe_html($_POST['content'])) : '';
		$blog_id = Http::post('blog_id', 'int', 0);
		$to_comment_id = Http::post('to_comment_id', 'int', 0);
		$to_uid = 0;
		if(!$blog_id)
		{
			out(0, '参数错误 blog_id');
		}
		$_Blog = load_model('blog');
		$_Blog_Comment = load_model('blog_comment');
		$blog = $_Blog->getRow(array('blog_id'=>$blog_id));
		if(!$blog)
		{
			out(0, '动态不存在');
		}
		$to_uid = $blog['uid'];
		$source_info = array();
		if($to_comment_id)
		{
			$source_info = $_Blog_Comment->getComment($to_comment_id);
			if(!$source_info)
			{
				out(0, '回复的评论不存在');
			}
			if($source_info['blog_id'] != $blog_id)
			{
				out(0, '回复的评论所属的动态数据错误');
			}
			$to_uid = $source_info['user']['id'];
			$source_info['source_info'] = array();
		}
		db()->begin();
		try{
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
			$comment = array(
				'blog_id'=>$blog_id,
				'uid'=>$this->uid,
				'content'=>$content,
				'to_comment_id'=>$to_comment_id,
				'to_uid'=>$to_uid,
				'source_info'=>serialize($source_info),
				'ctime'=>time(),
				'from'=>$from,
			);
			$comment_id = $_Blog_Comment->insert($comment);
			if(!$comment_id) throw new Exception('评论发布失败！');
			if(!$_Blog->increment('comment_count', array('blog_id'=>$blog_id), 1)) throw new Exception('评论发布失败！');
			//推送
			$to = $to_uid ? $to_uid :$blog['uid'];
			$comment = $_Blog_Comment->getComment($comment_id);
			push('db')->add('H_PUSH',array(
				'app' => $this->app,'act' => $this->act,'from' =>$this->uid,'to'=>$to,'type' => 1,'ext'=> $comment
			));	
			db()->commit();
			out(1, '评论发布成功',array('comment'=>$comment));
		}catch(Exception $e)
		{
			db()->rollback();
			Out(0, $e->getMessage());
		}
	}
	
	/**
	 * 删除评论
	 */
	public function delete()
	{
		$comment_id = Http::post('comment_id', 'int', 0);
		if(!$comment_id)
		{
			out(0, '参数错误 comment_id');
		}
		$_Blog = load_model('blog');
		$_Blog_Comment = load_model('blog_comment');
		$comment = $_Blog_Comment->getRow(array('comment_id'=>$comment_id));
		if(!$comment)
		{
			out(0, '不存在此评论');
		}
		if($comment['uid'] == $this->uid || $comment['to_uid'] == $this->uid)
		{
			db()->begin();
			try{
				//删除微博评论表
				if(!$_Blog_Comment->delete(array('comment_id'=>$comment_id),true)) throw new Exception('删除评论失败！');
				if(!$_Blog->decrement('comment_count', array('blog_id'=>$comment['blog_id']), 1)) throw new Exception('删除评论失败！');
				db()->commit();
				out(1, '删除评论成功');
			}catch(Exception $e)
			{
				db()->rollback();
				Out(0, $e->getMessage());
			}
		}else{
			out(0, '你无权删除此评论');
		}
	}
}