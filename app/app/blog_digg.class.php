<?php
/**
 * 呼啦圈赞
 */
class Blog_digg_Api extends Api
{
	public function __construct(){
		parent::_init();
	}

	public function index(){
		
	}
	/**
	 * 赞
	 */
	public function add()
	{
		$blog_id= Http::post('blog_id', 'int', 0);
		if(!$blog_id)
		{
			out(0, '参数错误 blog_id');
		}
		$_Blog= load_model('blog');
		$_Blog_Digg = load_model('blog_digg');
		$blog = load_model('blog')->getRow(array('blog_id'=>$blog_id));
		if(!$blog)
		{
			out(0, '动态不存在');
		}
		if($_Blog_Digg->getRow(array('uid'=>$this->uid,'blog_id'=>$blog_id)))
		{
			out(0, '已赞过');
		}
		db()->begin();
		try{
			$digg= array(
				'uid'=>$this->uid,
				'blog_id'=>$blog_id,
				'ctime'=>time(),
			);
			$digg_id = $_Blog_Digg->insert($digg);
			if(!$digg_id) throw new Exception('失败！');
			if(!$_Blog->increment('digg_count', array('blog_id'=>$blog_id), 1)) throw new Exception('失败！');
			db()->commit();
			out(1, '成功');
		}catch(Exception $e)
		{
			db()->rollback();
			Out(0, $e->getMessage());
		}
	}
	
	/**
	 * 取消赞
	 */
	public function delete()
	{
		$blog_id= Http::post('blog_id', 'int', 0);
		if(!$blog_id)
		{
			out(0, '参数错误 blog_id');
		}
		$_Blog= load_model('blog');
		$_Blog_Digg = load_model('blog_digg');
		$blog = $_Blog->getRow(array('blog_id'=>$blog_id));
		if(!$blog)
		{
			out(0, '动态不存在');
		}
		if(!$_Blog_Digg->getRow(array('uid'=>$this->uid,'blog_id'=>$blog_id)))
		{
			out(0, '未赞过');
		}
		db()->begin();
		try{
			if(!$_Blog_Digg->delete(array('uid'=>$this->uid,'blog_id'=>$blog_id),true)) throw new Exception('取消赞失败！');
			if(!$_Blog->decrement('digg_count', array('blog_id'=>$blog_id), 1)) throw new Exception('取消赞失败！！');
			db()->commit();
			out(1, '取消赞成功');
		}catch(Exception $e)
		{
			db()->rollback();
			Out(0, $e->getMessage());
		}
	}
}