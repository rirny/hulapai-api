<?php
class Favorite_model Extends Model
{
	protected $_table = 't_favorite';
	protected $_key = 'id';

	private $detail = Null;
	private $simple = Null;
	
	public function __construct(){
		parent::__construct();
	}

	public function getAll($param=array(), $limit='', $order='', $cache=false, $out=false)
	{
		$result = parent::getAll($param, $limit, $order, $cache, $out);
		if($result && $out)
		{
			foreach($result as $key=> $item)
			{
				$item = $this->Format($item);
				switch($item['type'])
				{
					case 1:
						break;
					case 2:
						break;
					default : // 日志
						$_Entity = load_model('space');						
						break;
				}
				$item['source'] = $_Entity->getRow($item['source'], true);
				$result[$key] = $item;
			}			
		}
		return $result;
	}

	public function getRow($param, $out = false, $field='*', $order='')
	{
		$result = parent::getRow($param, $out, $field, $order);
		if($result && $out)
		{
			switch($result['type'])
			{
				case 1:
					break;
				case 2:
					break;
				default : // 日志
					$_Entity = load_model('space');						
					break;
			}
			$item['source'] = $_Entity->getRow($item['source'], true);
		}
		return $result;
	}
}
