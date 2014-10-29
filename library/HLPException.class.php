<?php
/**
 * @author Liu
 * @since v1.0 
 */
class HLPException extends Exception
{
	public $back = ''; // 返回页
	public $goto = ''; // goto
	public $tm = 0; // 时间秒

    public function __construct($message = '', $code = 0, $back='', $goto='')
    {
        parent::__construct($message, $code);		
		// 404
		// 301
		// 302
		$this->back = $back;
		$this->goto = $goto;
		$this->code = $code;
    }
}