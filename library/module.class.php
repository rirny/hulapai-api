<?php
Abstract class Module
{
	
	public $app = '';
	public $act = '';
	
	public $smarty = Null;

	public $module = "";

	protected $refer = '';

	public function __construct(){		
		
	}
	
	protected function _init()
	{		
			
	}	

	public function __destruct(){
		if (class_exists('Db'))
		{
			db()->close();
		}
	}

	protected function getError()
	{
		return $error_message;
	}
	
	
	protected function assign($key, $value)
	{
		$this->_smarty();
		$this->smarty->assign($key, $value);
	}

	protected function display($tpl, $tm)
	{	
		$this->_smarty();		
		$tpl = $tpl . ".html";
		if($this->smarty->templateExists($tpl))
		{
			$this->smarty->display($tpl);
		}else
		{
			throw new HLPException('模板文件不存在！<BR/>' . $tpl, 404);
		}
		exit;
	}

	protected function fetch($tpl, $tm)
	{
		$this->_smarty();
		return $this->smarty->fetch($tpl . ".html");
	}

	protected function cache($key, $value, $tm)
	{
	
	}

	private function _smarty()
	{	
		if($this->smarty == Null)
		{			
			import('smarty/smarty');			
			$this->smarty = new Smarty;
			$this->smarty->caching = SMARTY_CACHEING;
			$this->smarty->template_dir = SMARTY_TPL_DIR;
			$this->smarty->compile_dir = SMARTY_COMPILE_DIR;
			$this->smarty->cache_dir = SMARTY_CACHE_DIR;
			$this->smarty->left_delimiter = '<!--{';
			$this->smarty->right_delimiter = '}-->';

			$this->smarty->assign('ROOT', ROOT);
			$this->smarty->assign('STATIC_PATH', STATIC_PATH);
			$this->smarty->assign('JS', ROOT . "/static/js");
			$this->smarty->assign('IMG', ROOT . "/static/images");
			$this->smarty->assign('CSS', ROOT . "/static/css");			
			$this->smarty->assign('curl', Http::curl());
			$refer = !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : "/" . $this->app;
			if(strpos($refer, '/') === 0) $refer = substr($refer, 1);
			if(strtolower(str_replace("/", '', $refer)) == 'index') $refer = '';
			$this->refer = $refer;
			$this->smarty->assign('refer', $refer);
			$this->is_mobile = Http::is_mobile();
			$this->assign('is_mobile', $this->is_mobile);
		}

	}

	protected function error($message, $back, $goto, $time = 0)
	{
		$this->assign('message', $e->getMessage());
		$this->assign('back', $backUrl);
		$this->assign('goto', $goto);
		$this->assign('time', $time);
		$this->display('error');
	}
	
}
