<?php
// 呼啦圈
class Blog_model Extends Model
{
	protected $_db = NULL;
	protected $_table = 'ts_blog';
	protected $_key = 'blog_id';
	protected $_table_user = 't_user';
		
	public function __construct(){
		parent::__construct();	
	}
	
	/**
	 * 获取动态数据
	 */
	public function getBlog($blog_id){
		$blogs = $this->getBlogsByBlogIds($blog_id);
		return $blogs[0];
	}
	
	/**
	 * 获取所有动态数
	 */
	public function getBlogsNum($uids = ''){
		$sql = "SELECT COUNT(*) AS n FROM $this->_table WHERE 1";
		if($uids){
			$sql .= " and uid in ($uids)";
		}
		$result = db()->fetchRow($sql);
		return $result['n'] ? $result['n'] : 0;
	}
	
	/**
	 * 获取所有动态
	 */
	public function getBlogs($uids = '',$offset=0,$pagesize=20){
		$tmpSql = " select blog_id from $this->_table where 1";
		if($uids){
			$tmpSql .= " and uid in ($uids)";
		}
		$tmpSql .= " ORDER BY modify_time desc,blog_id DESC LIMIT $offset,$pagesize";
		$sqlBlogIds = "SELECT GROUP_CONCAT(t.blog_id) as blog_ids FROM ($tmpSql) t";
		$result = db()->fetchRow($sqlBlogIds);
		$blogIds = $result['blog_ids'] ? $result['blog_ids'] : '';
		if(!$blogIds) return false;
		return $this->getBlogsByBlogIds($blogIds);
	}
	
	/**
	 * 获取所有动态
	 */
	public function getBlogsByBlogIds($blogIds = ''){
		$sql = "SELECT * from  $this->_table where blog_id in ($blogIds) ORDER BY modify_time desc,blog_id DESC";
		$result = db()->fetchAll($sql);
		if($result){
			$_User = load_model('user');
			foreach($result as &$_result){
				$_result['publish_time'] = date('Y-m-d H:i:s',$_result['publish_time']);
				$_result['attachs'] = unserialize($_result['attachs']);
				$_result['source_info'] = unserialize($_result['source_info']);
				if($_result['source_info']){
					$sourceTotal = $this->getBlogTotalInfo($_result['source_id']);
					$sourceTotal && $_result['source_info'] = array_merge($_result['source_info'],$sourceTotal);
				}
				$_result['user'] = $_User->getBaseUserById($_result['uid']);
				unset($_result['client_ip']);
				unset($_result['source_id']);
			}
		}	
		/*
		$sqlSourceIds = "SELECT GROUP_CONCAT(source_id) as source_ids FROM $this->_table where blog_id in ($blogIds) and source_id > 0";
		$result = db()->fetchRow($sqlSourceIds);
		$sourceIds = $result['source_ids'] ? $result['source_ids'] : 0;
		$sourceIds = array_unique(explode(',',$sourceIds));
		$sourceIds = implode(',',$sourceIds);
		$sql = "SELECT a.*,b.comment_count as source_comment_count,b.repost_count as source_repost_count,b.digg_count as source_digg_count,c.nickname,c.gender,c.avatar,c.hulaid " .
				"FROM $this->_table AS a " .
				"left join (select blog_id,comment_count,repost_count,digg_count from $this->_table where blog_id in ($sourceIds)) as b on a.source_id = b.blog_id " .
				"left join $this->_table_user as c on a.uid = c.id " .
				"where a.blog_id in ($blogIds) " .
				"ORDER BY a.blog_id DESC";
		$result = db()->fetchAll($sql);
		if($result){
			foreach($result as &$_result){
				$_result['attachs'] = unserialize($_result['attachs']);
				$_result['source_info'] = unserialize($_result['source_info']);
				if($_result['source_info']){
					$_result['source_info']['comment_count'] = $_result['source_comment_count'];
					$_result['source_info']['repost_count'] = $_result['source_repost_count'];
					$_result['source_info']['digg_count'] = $_result['source_digg_count'];
				}
				$_result['user'] = array(
					'_id'=>$_result['uid'],
					'nickname'=>$_result['nickname'],
					'gender'=>$_result['gender'],
					'avatar'=>$_result['avatar'],
					'hulaid'=>$_result['hulaid']
				);
				$_result['publish_time'] = date('Y-m-d H:i:s',$_result['publish_time']);
				unset($_result['uid']);
				unset($_result['nickname']);
				unset($_result['gender']);
				unset($_result['avatar']);
				unset($_result['hulaid']);
				unset($_result['source_comment_count']);
				unset($_result['source_repost_count']);
				unset($_result['source_digg_count']);
				unset($_result['client_ip']);
				unset($_result['source_id']);
			}
		}
		*/
		return $result;
	}
	
	
	public function getBlogTotalInfo($blogId){
		$sql = "select blog_id,comment_count,repost_count,digg_count from $this->_table where blog_id = $blogId limit 1";
		return db()->fetchRow($sql);
	}
}