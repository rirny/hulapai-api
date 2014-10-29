<?php
class Delete_Log_model Extends Model
{
	protected $_table = 't_delete_logs';
	protected $_key = 'id';

	public function __construct(){
		parent::__construct();
	}
}