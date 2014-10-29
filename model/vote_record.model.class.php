<?php
class Vote_record_model Extends Model
{
	protected $_table = 't_vote_record';
	protected $_key = 'id';	

	
	public function __construct(){
		parent::__construct();
	}
	
	public function getRecord($vote){
		$sql = "select count(1) as num,`option` from $this->_table where vote = $vote group by `option`";
		$record = db()->fetchAll($sql);
		return $record;
	}	 
}