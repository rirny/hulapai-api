<?php
/**
 * @author Liu
 * @since v1.0 
 */
class HlpException extends Exception
{
    public function __construct($msg = '', $code = 0)
    {
        parent::__construct($msg, $code);
		if($code == 1) // 写日志
		{
					
		}        
    }
	
	// 
	public function logs($msg)
	{
		$dir = LOG_PATH . "/data/log/" . date("Ym");
		if(!is_dir($dir)) _mkdir($dir, 0777);		
		$date = date('Y-m-d H:i:s'); 		
		$log.= $date ."\t". $action. "\t" . $uid . "\n";
		$log.= "SQL:\t" . $sql . "\n";
		$log.= "message:\t" . json_encode($message) . "\n";
		error_log($log, 3,  $dir . "/" . date('H') . ".log"); 
	}
}