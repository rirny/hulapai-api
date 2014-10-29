<?php
// 微博评论
class Feed_comment_model Extends Model
{
	protected $_db = NULL;
	protected $_table = 'ts_feed_comment';
	protected $_table_user = 't_user';
	protected $_key = 'comment_id';
	
	public function __construct(){
		parent::__construct();	
	}
	
	
	/**
	 * 获取微博评论数
	 */
	public function getCommentsNum($feed_id){
		$result = db()->fetchRow("SELECT COUNT(*) AS n FROM $this->_table WHERE feed_id='$feed_id'");
		return $result['n'] ? $result['n'] : 0;
	}
	
	/**
	 * 获取微博评论
	 */
	public function getComments($feed_id,$offset=0,$pagesize=20){
		$sql = "SELECT comment_id,feed_id,uid,content,from_unixtime(ctime,'%Y-%m-%d %H:%i:%s') as ctime,source_info FROM $this->_table WHERE feed_id='$feed_id'";
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
		$sql = "SELECT c.comment_id,c.feed_id,c.uid,u.firstname,u.lastname,u.teacher,u.nickname,u.gender,u.avatar,u.hulaid,c.content,from_unixtime(c.ctime,'%Y-%m-%d %H:%i:%s') as ctime,c.source_info " .
				"FROM $this->_table AS c " .
				"LEFT JOIN $this->_table_user AS u ON c.uid=u.id " .
				"WHERE c.feed_id='$feed_id' " .
				"ORDER BY comment_id DESC";
		$comments = db()->fetchAll($sql);
		if($comments){
			foreach($comments as &$comment){
				$comment['source_info'] = unserialize($comment['source_info']);
				$comment['user'] = array(
					'_id'=>$comment['uid'],
					'nickname'=>$comment['nickname'],
                    'firstname' => $comment['firstname'],
                    'lastname' => $comment['lastname'],
                    'teacher' => $comment['teacher'],
					'gender'=>$comment['gender'],
					'avatar'=>$comment['avatar'],
					'hulaid'=>$comment['hulaid']
				);
				unset($comment['uid']);
				unset($comment['nickname']);
				unset($comment['gender']);
				unset($comment['avatar']);
				unset($comment['hulaid'], $comment['firstname'], $comment['lastname']);
			}
		}
		*/
		return $comments;
	}
	
	/**
	 * 获取评论详细数据
	 */
	public function getComment($comment_id){
		$sql = "SELECT comment_id,feed_id,uid,content,from_unixtime(ctime,'%Y-%m-%d %H:%i:%s') as ctime,source_info FROM $this->_table WHERE comment_id='$comment_id' limit 1";
		$comment = db()->fetchRow($sql);
		if($comment){
			$_User = load_model('user');
			$comment['source_info'] = unserialize($comment['source_info']);
			$comment['user'] = $_User->getBaseUserById($comment['uid']);
			unset($comment['uid']);
		}
		/*
		$sql = "SELECT c.comment_id,c.feed_id,c.uid,u.firstname,u.lastname,u.teacher,u.nickname,u.avatar,u.hulaid,c.content,from_unixtime(c.ctime,'%Y-%m-%d %H:%i:%s') as ctime,c.source_info " .
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
                'firstname' => $comment['firstname'],
                'lastname' => $comment['lastname'],
                'teacher' => $comment['teacher'],
				'gender'=>$comment['gender'],
				'avatar'=>$comment['avatar'],
				'hulaid'=>$comment['hulaid']
			);
			unset($comment['uid']);
			unset($comment['nickname']);
			unset($comment['gender']);
			unset($comment['avatar']);
			unset($comment['hulaid'], $comment['firstname'], $comment['lastname']);
		}
		*/
		return $comment;
	}
}
