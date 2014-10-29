<?php
// 微博
class Feed_model Extends Model
{
	protected $_db = NULL;
	protected $_table = 'ts_feed';
	protected $_key = 'feed_id';
	protected $_table_user = 't_user';
		
	public function __construct(){
		parent::__construct();	
	}
	
	/**
	 * 获取微博数据
	 */
	public function getFeed($feed_id){
		$feeds = $this->getFeedsByFeedIds($feed_id);
		return $feeds[0];
	}
	
	/**
	 * 获取所有微博数
	 */
	public function getFeedsNum($uids = ''){
		$sql = "SELECT COUNT(*) AS n FROM $this->_table WHERE 1";
		if($uids){
			$sql .= " and uid in ($uids)";
		}else{
			$sql .= " and uid !=1";
		}
		$result = db()->fetchRow($sql);
		return $result['n'] ? $result['n'] : 0;
	}
	
	/**
	 * 获取所有微博
	 */
	public function getFeeds($uids = '',$offset=0,$pagesize=20){
		$tmpSql = " select feed_id from $this->_table where 1";
		if($uids){
			$tmpSql .= " and uid in ($uids)";
		}else{
			$tmpSql .= " and uid !=1";
		}
		$tmpSql .= " ORDER BY publish_time desc,feed_id DESC LIMIT $offset,$pagesize";
		$sqlFeedIds = "SELECT GROUP_CONCAT(t.feed_id) as feed_ids FROM ($tmpSql) t";
		$result = db()->fetchRow($sqlFeedIds);
		$feedIds = $result['feed_ids'] ? $result['feed_ids'] : '';
		if(!$feedIds) return false;
		return $this->getFeedsByFeedIds($feedIds);
	}
	
	/**
	 * 获取所有微博
	 */
	public function getFeedsByFeedIds($feedIds = ''){
		$sql = "SELECT * from  $this->_table where feed_id in ($feedIds) ORDER BY publish_time desc,feed_id DESC";
		$result = db()->fetchAll($sql);
		if($result){
			$_User = load_model('user');
			foreach($result as &$_result){
				$_result['publish_time'] = date('Y-m-d H:i:s',$_result['publish_time']);
				$_result['attachs'] = unserialize($_result['attachs']);
				$_result['source_info'] = unserialize($_result['source_info']);
				if($_result['source_info']){
					$sourceTotal = $this->getFeedTotalInfo($_result['source_id']);
					$sourceTotal && $_result['source_info'] = array_merge($_result['source_info'],$sourceTotal);
				}
				$_result['user'] = $_User->getBaseUserById($_result['uid']);
				unset($_result['client_ip']);
				unset($_result['source_id']);
			}
		}	
		/*
		//获取父类微博id
		$sqlSourceIds = "SELECT GROUP_CONCAT(source_id) as source_ids FROM $this->_table where feed_id in ($feedIds) and source_id > 0";
		$result = db()->fetchRow($sqlSourceIds);
		$sourceIds = $result['source_ids'] ? $result['source_ids'] : 0;
		$sourceIds = array_unique(explode(',',$sourceIds));
		$sourceIds = implode(',',$sourceIds);
		$sql = "SELECT a.*,b.comment_count as source_comment_count,b.repost_count as source_repost_count,b.collect_count as source_collect_count,b.digg_count as source_digg_count,c.firstname,c.lastname,c.teacher,c.nickname,c.gender,c.avatar,c.hulaid " .
				"FROM $this->_table AS a " .
				"left join (select feed_id,comment_count,repost_count,collect_count,digg_count from $this->_table where feed_id in ($sourceIds)) as b on a.source_id = b.feed_id " .
				"left join $this->_table_user as c on a.uid = c.id " .
				"where a.feed_id in ($feedIds) " .
				"ORDER BY a.feed_id DESC";
		$result = db()->fetchAll($sql);
		if($result){
			foreach($result as &$_result){
				$_result['attachs'] = unserialize($_result['attachs']);
				$_result['source_info'] = unserialize($_result['source_info']);
				if($_result['source_info']){
					$_result['source_info']['comment_count'] = $_result['source_comment_count'];
					$_result['source_info']['repost_count'] = $_result['source_repost_count'];
					$_result['source_info']['collect_count'] = $_result['source_collect_count'];
					$_result['source_info']['digg_count'] = $_result['source_digg_count'];
				}
				$_result['user'] = array(
					'_id'=>$_result['uid'],
					'nickname'=>$_result['nickname'],
                    'firstname' => $_result['firstname'],
                    'lastname' => $_result['lastname'],
                    'teacher' => $_result['teacher'],
					'gender'=>$_result['gender'],
					'avatar'=>$_result['avatar'],
					'hulaid'=>$_result['hulaid']
				);
				$_result['publish_time'] = date('Y-m-d H:i:s',$_result['publish_time']);
				unset($_result['uid']);
				unset($_result['nickname']);
				unset($_result['gender']);
				unset($_result['avatar']);
				unset($_result['hulaid'], $_result['firstname'],$_result['lastname']);
				unset($_result['source_comment_count']);
				unset($_result['source_repost_count']);
				unset($_result['source_repost_count']);
				unset($_result['source_collect_count']);
				unset($_result['client_ip']);
				unset($_result['source_id']);
			}
		}
		*/
		return $result;
	}
	
	
	public function getFeedTotalInfo($feedId){
		$sql = "select feed_id,comment_count,repost_count,collect_count,digg_count from $this->_table where feed_id = $feedId limit 1";
		return db()->fetchRow($sql);
	}
}
