<?php
// 呼啦圈评论
class Blog_comment_model Extends Model
{
	protected $_db = NULL;
	protected $_table = 'ts_blog_comment';
	protected $_table_user = 't_user';
	protected $_key = 'comment_id';
	
	public function __construct(){
		parent::__construct();	
	}
	
	
	/**
	 * 获取动态评论数
	 */
	public function getCommentsNum($uids='',$blog_id){
		$sql = "SELECT COUNT(*) AS n FROM $this->_table WHERE blog_id='$blog_id'";
		if($uids){
			$sql .= " and uid in ($uids) and to_uid in ($uids)";
		}
		$result = db()->fetchRow($sql);
		return $result['n'] ? $result['n'] : 0;
	}
	
	/**
	 * 获取动态评论
	 */
	public function getComments($uids='',$blog_id,$offset=0,$pagesize=20){
		$sql = "SELECT comment_id,blog_id,uid,content,from_unixtime(ctime,'%Y-%m-%d %H:%i:%s') as ctime,source_info FROM $this->_table WHERE blog_id='$blog_id'";
		if($uids){
			$sql .= " and uid in ($uids) and to_uid in ($uids)";
		}
		$sql .= " ORDER BY ctime desc,comment_id DESC";
		$comments = db()->fetchAll($sql);
		if($comments){
			$_User = load_model('user');
			foreach($comments as &$comment){
				$comment['source_info'] = unserialize($comment['source_info']);
				$comment['user'] = $_User->getBaseUserById($comment['uid']);
				unset($comment['uid']);
			}
		}
		/*
		$sql = "SELECT c.comment_id,c.blog_id,c.uid,u.nickname,u.gender,u.avatar,u.hulaid,c.content,from_unixtime(c.ctime,'%Y-%m-%d %H:%i:%s') as ctime,c.source_info " .
				"FROM $this->_table AS c " .
				"LEFT JOIN $this->_table_user AS u ON c.uid=u.id " .
				"WHERE c.blog_id='$blog_id'";
		if($uids){
			$sql .= " and c.uid in ($uids) and c.to_uid in ($uids)";
		}
		$sql .= " ORDER BY c.comment_id DESC";
		$comments = db()->fetchAll($sql);
		if($comments){
			foreach($comments as &$comment){
				$comment['source_info'] = unserialize($comment['source_info']);
				$comment['user'] = array(
					'_id'=>$comment['uid'],
					'nickname'=>$comment['nickname'],
					'gender'=>$comment['gender'],
					'avatar'=>$comment['avatar'],
					'hulaid'=>$comment['hulaid']
				);
				unset($comment['uid']);
				unset($comment['nickname']);
				unset($comment['gender']);
				unset($comment['avatar']);
				unset($comment['hulaid']);
			}
		}
		*/
		return $comments;
	}
	
	/**
	 * 获取评论详细数据
	 */
	public function getComment($comment_id){
		$sql = "SELECT comment_id,blog_id,uid,content,from_unixtime(ctime,'%Y-%m-%d %H:%i:%s') as ctime,source_info FROM $this->_table WHERE comment_id='$comment_id' limit 1";
		$comment = db()->fetchRow($sql);
		if($comment){
			$_User = load_model('user');
			$comment['source_info'] = unserialize($comment['source_info']);
			$comment['user'] = $_User->getBaseUserById($comment['uid']);
			unset($comment['uid']);
		}
		/*
		$sql = "SELECT c.comment_id,c.blog_id,c.uid,u.nickname,u.avatar,u.hulaid,c.content,from_unixtime(c.ctime,'%Y-%m-%d %H:%i:%s') as ctime,c.source_info " .
				"FROM $this->_table AS c " .
				"LEFT JOIN $this->_table_user AS u ON c.uid=u.id " .
				"WHERE c.comment_id='$comment_id' " .
				"limit 1";
		$comment = db()->fetchRow($sql);
		if($comment){
			$comment['source_info'] = unserialize($comment['source_info']);
			$comment['user'] = array(
				'_id'=>$comment['uid'],
				'nickname'=>$comment['nickname'],
				'gender'=>$comment['gender'],
				'avatar'=>$comment['avatar'],
				'hulaid'=>$comment['hulaid']
			);
			unset($comment['uid']);
			unset($comment['nickname']);
			unset($comment['gender']);
			unset($comment['avatar']);
			unset($comment['hulaid']);
		}
		*/
		return $comment;
	}
}
