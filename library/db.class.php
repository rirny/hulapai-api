<?php

class Db
{
    protected $_master = null;
    protected $_slave = null;
	
	protected $_config = array(
		'master' => array(),
		'slave' => array(),
	);

	protected $_option = array(
        'driver' => 'mysql',
        'host' => '127.0.0.1',
        'charset' => 'utf8',
        'persistent' => false,
        'db' => '',
        'username' => '',
        'password' => '',
    );

    protected $_always_master = 0;
    protected $_fetch_mode = PDO::FETCH_ASSOC;

	private $_conn = Null;

	public function __construct(array $config, $always_master = 0)
    {		
        $this->_config['master'] = isset($config['master']) ? $config['master'] : $config;
        !isset($config['slave']) || $this->_config['slave'] = $config['slave'];		
        $this->_always_master = $always_master;
    }

	private function connect($config)
	{		
		$config = array_merge($this->_option, $config);
		extract($config);
		$dsn = sprintf("%s:host=%s;dbname=%s", $driver, $host, $dbname);
		$option = array(
			PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES '". $charset ."'",
			PDO::ATTR_PERSISTENT => $persistent,
			PDO::ATTR_AUTOCOMMIT => 0,
			PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, 
			PDO::ATTR_AUTOCOMMIT => true
		);
		return new PDO($dsn, $username, $password, $option);		
	}

	private function getMaster()
	{
		if (null === $this->_master) {
            $this->_master = self::connect($this->_config['master']);
        }		
        return $this->_master;
	}

	private function getSlave()
	{
		if (null === $this->_slave) {
            $this->_slave = self::connect($this->_config['slave']);
        }		
        return $this->_slave;
	}

	public function begin()
	{
		$this->getMaster()->setAttribute(PDO::ATTR_AUTOCOMMIT, FALSE);
		$this->getMaster()->beginTransaction();
	}
	
	public function commit()
	{
		$this->getMaster()->commit();
	}
	
	public function rollback()
	{
		$this->getMaster()->rollBack();
	}

    public function query($sql, $bind = array())
    {		
        if (!is_array($bind)) {
            $bind = array($bind);
        }		
		try
		{
			$stmt = $this->getMaster()->prepare($sql);
			// return $stmt->execute($bind);			
			$stmt->execute($bind);			
			return $stmt;
		}catch(PDOException $e)
		{
			die($e->getMessage());
		}       
    }

	public function increment($table, $key, $where='', $step=1) 
	{
        $sql = 'UPDATE ' . $this->quoteIdentifier($table, true) . ' SET `' . $key .'`=`' . $key . '` +' . $step . (($where) ? " WHERE $where" : '');      
        $stmt = $this->getMaster()->query($sql);
        $result = $stmt->rowCount();
        return $result;
    }  
  
    /** 
     * 数据自减 
     * @param string $key KEY名称 
     */  
    public function decrement($table, $key, $where='', $step=1) 
	{
        $sql = 'UPDATE ' . $this->quoteIdentifier($table, true) . ' SET `' . $key .'`=`' . $key . '` -' . $step . (($where) ? " WHERE $where" : '');      
        $stmt = $this->getMaster()->query($sql);
        $result = $stmt->rowCount();
        return $result;  
    }

	public function prepare($sql, $bind = array())
    {
        if (!is_array($bind)) {
            $bind = array($bind);
        }
		try{
			$stmt = $this->getMaster()->prepare($sql);			
			$stmt->execute($bind);
			$stmt->setFetchMode($this->_fetch_mode);
			return $stmt;
		}catch(PDOException $e)
		{
			Out(0, $e->getMessage());
		}        
    }

	public function lastInsertId()
    {
        return $this->getMaster()->lastInsertId();
    }

	public function insert($table, array $bind)
    {
        $cols = array();
        $vals = array();
        foreach ($bind as $col => $val) {
            $cols[] = $this->quoteIdentifier($col);
            $vals[] = '?';
        }
		$sql = "INSERT INTO ". $this->quoteIdentifier($table) .' ('. implode(', ', $cols) . ') VALUES (' . implode(', ', $vals) . ')';
		//echo $sql;
		//print_r($bind);
		$res = $this->query($sql, array_values($bind));		
        $lastid = $this->lastInsertId();
        return $lastid ? $lastid : $res;
    }

	public function update($table, array $bind, $where = '')
    {
        $set = array();
        $i = 0;
        foreach ($bind as $col => $val) {
            $val = '?';
            $set[] = $this->quoteIdentifier($col) . ' = ' . $val;
        }
        $where = $this->where($where);
		$sql = 'UPDATE ' . $this->quoteIdentifier($table) . ' SET ' . implode(', ', $set) . (($where) ? " WHERE $where" : '');
		$stmt = $this->query($sql, array_values($bind));		
        return $stmt->rowCount();
    }

	public function save($table, array $bind, $where = '')
    {
        $where = $this->where($where);
        $sql = 'SELECT COUNT(*) FROM '. $this->quoteIdentifier($table) . (($where) ? " WHERE $where" : '');
        if ($this->fetchOne($sql)) {
            return $this->update($table, $bind, $where);
        } else {
            return $this->insert($table, $bind);
        }
    }

	public function delete($table, $where = '')
    {
        $where = $this->where($where);
		$sql = 'DELETE FROM ' . $this->quoteIdentifier($table) . (($where) ? " WHERE $where" : '');		
		$stmt = $this->query($sql);
		return $stmt->rowCount();       
    }

	public function fetchAll($sql, $bind = array(), $fetch_mode = null)
    {
        $fetch_mode !== null || $fetch_mode = $this->_fetch_mode;
        $stmt = $this->prepare($sql, $bind);		
        return $stmt->fetchAll($fetch_mode);
    }

	public function fetchRow($sql, $bind = array(), $fetch_mode = null)
    {	
        $fetch_mode !== null || $fetch_mode = $this->_fetch_mode;
        $stmt = $this->prepare($sql, $bind);
        return $stmt->fetch($fetch_mode);
    }
	// 一列
	public function fetchCol($sql, $bind = array())
    {
        $stmt = $this->prepare($sql, $bind);
        return $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    }

	public function fetchPairs($sql, $bind = array())
    {
        $stmt = $this->prepare($sql, $bind);
        $data = array();
        while (true == ($row = $stmt->fetch(PDO::FETCH_NUM))) {
            $data[$row[0]] = $row[1];
        }
        return $data;
    }

	public function fetchOne($sql, $bind = array())
    {
        $stmt = $this->prepare($sql, $bind);
        $result = $stmt->fetchColumn(0);
        return $result;
    }
	
    /**
     * 组装where条件
     *
     * array('x=?'=>'x')
     * array('x=1', 'y=2')
     * array('x>?'=>2, 'y=?'=>'y')
     * array('x in?'=>array(1,2,3))
     * array('x between?'=>array(1,2))
     * array('x like?'=>'%x')
     * array('x=?'=>x, 'or'=>array('x=?'=>'x', 'y>?'=>2))
     *
     * @param string|array $where
     * @return string
     */
    public function where($where)
    {
        if (!is_array($where)) {
            return $where;
        }
        $where_str = '';
        $i = 0;
        foreach ($where as $cond => $term) {
            if (strtolower($cond) == 'or') {
                $where_str .= ($i ? ' OR ' : '') .'('. $this->where($term) .')';
            } else {
                if (!is_int($cond)) {
                    if (strpos($cond, 'in?')) {
                        $term = str_replace('in?', 'IN '. $this->_inExpr($term), $cond);
                    } elseif (strpos($cond, 'between?')) {
                        $term = str_replace('between?', 'BETWEEN '. $this->_betweenExpr($term), $cond);
                    } elseif (strpos($cond, 'like?')) {
                        $term = str_replace('like?', 'LIKE '. $this->quote($term), $cond);
                    } else {
                        $term = str_replace('?', $this->quote($term), $cond);
                    }
                }
                $where_str .= ($i ? ' AND ' : '') . $term;
            }
            $i++;
        }
        return $where_str;
    }

	public function quote($value)
    {
        if (is_array($value)) {
            foreach ($value as &$val) {
                $val = $this->quote($val);
            }
            return implode(', ', $value);
        } elseif (is_int($value)) {
            return $value;
        } elseif (is_float($value)) {
            return sprintf('%F', $value);
        }
        return "'" . addcslashes($value, "\000\n\r\\'\"\032") . "'";
    }

	/**
     * 生成in子句
     * @param string|array $value
     * @return string
     */
    protected function _inExpr($value)
    {
        return is_array($value) ? '('. $this->quote($value) .')' : $value;
    }

    /**
     * 生成between子句
     * @param string|array $value
     * @return string
     */
    protected function _betweenExpr($value)
    {
        return is_array($value) ? $this->quote($value[0]) .' AND '. $this->quote($value[1]) : $value;
    }

	 /**
	 * Quotes an identifier.
	 *
	 * <code>
	 * $adapter->quoteIdentifier('myschema.mytable')
	 * </code>
	 * Returns: "myschema"."mytable"
	 *
	 * <code>
	 * $adapter->quoteIdentifier(array('myschema','my.table'))
	 * </code>
	 * Returns: "myschema"."my.table"
	 *
	 * @param type $ident
	 * @return string The quoted identifier.
	 */
	public function quoteIdentifier($ident)
    {
        return $this->_quoteIdentifierAs($ident, null);
    }

	public function quoteColumnAs($ident, $alias)
    {
        return $this->_quoteIdentifierAs($ident, $alias);
    }	

	public function quoteTableAs($ident, $alias = null)
    {
        return $this->_quoteIdentifierAs($ident, $alias);
    }

	protected function _quoteIdentifierAs($ident, $alias = null, $as = ' AS ')
    {
        if (is_string($ident)) {
            $ident = explode('.', $ident);
        }
        if (is_array($ident)) {
            $segments = array();
            foreach ($ident as $segment) {
                $segments[] = $this->_quoteIdentifier($segment);
            }
            if ($alias !== null && end($ident) == $alias) {
                $alias = null;
            }
            $quoted = implode('.', $segments);
        } else {
            $quoted = $this->_quoteIdentifier($ident);
        }
        if ($alias !== null) {
            $quoted .= $as . $this->_quoteIdentifier($alias);
        }
        return $quoted;
    }
	
	protected function _quoteIdentifier($value)
    {
        $q = '`';
        return ($q . str_replace("$q", "$q$q", $value) . $q);
    }


	public function close()
    {
        $this->_master = null;
		$this->_slave = null;
    }	
}
