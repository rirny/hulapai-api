<?php
class Cms_Api extends Api
{

	public function __construct(){
		// parent::_init();		
	}	

	
	public function help()
	{
		$helps = load_model('cms')->getHelpList();
		if($helps){
			foreach($helps as &$help){
				$help['title'] = html_entity_decode(strip_tags($help['title']));
				$help['content'] = html_entity_decode(strip_tags($help['content']));
			}
		}
		out(1, '',$helps);
	}
	
}