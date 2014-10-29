<?php
/*
 * 关系模型抽象方法
*/
Abstract class Relation extends Model
{

	protected $db = Null;
	
    private $_prefix = 't_';		
	protected $_table = '';
	protected $_pk = 'id';
	protected $_cache_key = '';	
	protected $_timelife = '3600';

	public function whereExp($param)
	{
		$result = '';		
		if(is_array($param))
		{
			$where = array();
			foreach($param as $key => $item)
			{
				$pos = '='; $alias = '';
				if(strpos($key, ' '))
				{
					list($key, $pos) = explode(' ', $key);
				}	
				if(strpos($key, '.'))
				{
					list($alias, $key) = explode('.', $key);					
				}
				$alias && $key = ".`" . $key ."`" ;
				in_array($pos, array('=', '!=', '<>', '>', '<', '<=', '>=', 'in', 'like')) || $pos = '=';				
				if(strtoupper($pos) == 'IN')
				{
					$where[] = sprintf("%s %s ('%s')", $key, $pos, join("','", $item));
				}else if(strtoupper($pos) == 'LIKE')
				{
					$where[] = sprintf("%s like '%%%s%%'", $key, $item);
				}else{
					if(is_numeric($item))
					{
						$where[] = sprintf("%s %s %s", $key, $pos, $item);
					}else{
						$where[] = sprintf("%s %s '%s'", $key, $pos, $item);
					}					
				}
			}
			$result = join(' And ', $where);
		}else if(is_numeric($param))
		{
			$result = sprintf("`%s`='%d'", $this->_pk, $param);
		}else if(strpos($param, '=') || strpos($param, '>') || strpos($param, '<') || strpos($param, 'in') ){ // 包含 =、>、<、<>、in
			$result = $param;
		}
		//echo $result . "\n";
		return $result;
	}
	
}
