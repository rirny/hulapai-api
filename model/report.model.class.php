<?php
class Report_model Extends Model
{
	public $Y=0;
	public $M=0;
	public $D=0;
	public $H=0;
	public $DB=NULL;
	public $dbhandle=NULL;
	public function __construct($argv = array()){
		parent::__construct();
		$now = strtotime('1 hour ago');
		$argv[1] && $argv[1] = intval($argv[1]);
		$argv[2] && $argv[2] = intval($argv[2]);
		$argv[3] && $argv[3] = intval($argv[3]);
		$argv[4] && $argv[4] = intval($argv[4]);
		$Y = $argv[1];
		$M = $argv[2];
		$D = $argv[3];
		$H = $argv[4];
		if(!$Y) $Y = date('Y',$now);
		if(!$M) $M = date('m',$now);
		if(!$D) $D = date('d',$now);
		if(!$H) $H = date('H',$now);
		$this->Y = $Y;

		$this->M = str_pad($M, 2, '0', STR_PAD_LEFT);
		$this->D = str_pad($D, 2, '0', STR_PAD_LEFT);
		$this->H = str_pad($H, 2, '0', STR_PAD_LEFT);

		$this->DB = "report_{$this->Y}{$this->M}";
		$this->dbhandle = db('report');
		if(!$this->dbhandle) exit('db error');
		$this->checkDbExist();
	}
	
	public function checkDbExist(){
		$sql = "CREATE DATABASE IF NOT EXISTS `{$this->DB}` CHARACTER SET utf8 COLLATE utf8_general_ci";
		if(!$this->dbhandle->query($sql)) exit($this->DB.'create error');
		return true;
	}
	
	public function checkLogExist(){
		$sql = "CREATE TABLE IF NOT EXISTS `{$this->DB}`.`log_{$this->Y}{$this->M}{$this->D}`(
			`id` int(11) NOT NULL AUTO_INCREMENT,
			`url` varchar(255) DEFAULT NULL,
			`method` varchar(20) DEFAULT NULL,
			`app` varchar(100) DEFAULT NULL,
			`act` varchar(100) DEFAULT 'index',
			`ext` text,
			`agent` enum('web','wap','android','ios') DEFAULT 'web',
			`ip` char(15) DEFAULT NULL,
			`create_time` datetime DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY (`id`),
			KEY `app` (`app`),
			KEY `act` (`act`),
			KEY `agent` (`agent`),
			KEY `create_time` (`create_time`)
			)ENGINE=MYISAM CHARSET=utf8 COLLATE=utf8_general_ci";
		if(!$this->dbhandle->query($sql)) exit($this->DB.":table log_{$this->Y}{$this->M}{$this->D}".'create error');
		return true;
	}
}