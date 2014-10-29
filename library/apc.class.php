<?php
class Apc{

    /**
     * The default time-to-live value, 5 seconds.
     */
    protected $_ttl = 0;
    
    /**
     * The random factor value. 
     * Increment this value if you expect random page results.
     */
    protected $_randomFactor = 0;

    /**
     * Cache stack.
     */
    protected $_stack = array();
    
    public function __construct(){
        if (!extension_loaded('apc'))
            throw new Exception('APC extension is not loaded!');
    }
    
    /**
     * Set time-to-live value. The bigger the value the bigger time interval
     * between page refreshes.
     * @param int $ttl
     */
    public function setTtl($ttl){
        $this->_ttl = $ttl;
    }
    
    /**
     * Get time-to-live value.
     * @return int
     */
    public function getTtl(){
        return $this->_ttl;
    }
    
    /**
     * Set random factor, the bigger the value the more pages will be cached.
     * Use this if you expect random page results.
     * @param int $randomFactor
     */
    public function setRandomFactor($randomFactor){
        $this->_randomFactor = $randomFactor;
    }
    
    /**
     * Get random factor. 
     * @param int $randomFactor
     */
    public function getRandomFactor($randomFactor){
        return $this->_randomFactor;
    }
    
    /**
     * Save data to cache.
     * @param string $cacheId
     * @param mixed $data
     * @return boolean
     */
    public function save($cacheId, $data){
        $data = json_encode($data);
        return apc_store($cacheId, $data, $this->_ttl);
    }
    
    /**
     * Load data from cache.
     * @param string $cacheId
     * @return mixed
     */
    public function load($cacheId,$key=''){
        $success = false;
        $data = apc_fetch($cacheId, $success);
        if($success){
	        $data = json_decode($data,true);
	        $data = is_numeric($key) || $key ?  $data[$key] : $data;
	        return $data;
        }
        return null;
    }
    
    /**
     * Remove cache.
     * @param string $cacheId
     * @return boolean
     */
    public function remove($cacheId){
        return apc_delete($cacheId);
    }
    
    /**
     * Start capturing the output.
     * 
     * If more than one request will try to capture the output in the same time,
     * the first one should lock a mutex and the other will wait up to 5 seconds
     * before unlock or die.
     * 
     * @param string $cacheId
     */
    public function capture($cacheId){
        if ($this->_randomFactor > 0)
            $cacheId .= (rand() % $this->_randomFactor);
        

        $this->_loadCacheAndExit($cacheId);
        $this->_waitForLockOrDie($cacheId);
        $this->_loadCacheAndExit($cacheId);
        
        $this->save($cacheId.'lock', true);
        ob_start(array($this, '_flush'));
        ob_implicit_flush(false);
        array_push($this->_stack, $cacheId);
    }
    
    /**
     * Flush output and save it in cache when a request is finished.
     * This method is being used by output buffering feature in php.
     * @param string $data
     * @return string
     */
    public function _flush($data){
        $cacheId = array_pop($this->_stack);
        $this->save($cacheId, $data);
        $this->remove($cacheId.'lock');
        return $data;
    }
    
    /**
     * Start capturing the page output.
     * Generate an unique cache id based on the current request uri, 
     * get and post arrays. 

     * If more than one request will try to capture the output in the same time,
     * the first one should lock a mutex and the other will wait up to 5 seconds
     * before unlock or die.
     */
    public function capturePage(){
        $cacheId = md5(serialize(array(
            $_SERVER['REQUEST_URI'],
            $_GET,
            $_POST
        )));
        
        if ($this->_randomFactor > 0)
            $cacheId .= (rand() % $this->_randomFactor);
        
        
        $this->_loadPageCacheAndExit($cacheId);
        $this->_waitForLockOrDie($cacheId);
        $this->_loadPageCacheAndExit($cacheId);
        
        $this->save($cacheId.'lock', true);
        ob_start(array($this, '_flushPage'));
        ob_implicit_flush(false);
        array_push($this->_stack, $cacheId);
    }
    
    /**
     * Flush output and save it in cache when a request is finished.
     * This method is being used by output buffering feature in php.
     * 
     * This method remembers sent headers.
     * 
     * @param string $data
     * @return string
     */
    public function _flushPage($data){
        $cacheId = array_pop($this->_stack);
        $this->save($cacheId, array($data, headers_list()));
        $this->remove($cacheId.'lock');
        return $data;
    }
    
    /**
     * Load and flush cache.
     * @param string $cacheId
     */
    protected function _loadCacheAndExit($cacheId){
        $data = $this->load($cacheId);
        if ($data !== null) {
            echo $data;
            exit;
        }
    }
    
    /**
     * Load page cache, restore headers and flush response.
     * @param string $cacheId
     */
    protected function _loadPageCacheAndExit($cacheId){
        $pack = $this->load($cacheId);
        if (is_array($pack)) {
            $headers = $pack[1];
            foreach ($headers as $header)
                header($header);
            echo $pack[0];
            exit;
        }
    }
    
    /**
     * Wait up to 5 seconds for unlock or die.
     * @param string $cacheId
     */
    protected function _waitForLockOrDie($cacheId){
        $cacheId .= 'lock';
        $i = 500;
        while ($i > 0) {
            if (!$this->load($cacheId))
                return;
            $i--;
            usleep(10000);
        }

        exit();
    }
    
}

class Apc_handle extends Apc{
	private $_cacheData = null;
    private $_cObj = null;
    private static $_instance = null;

    public static function getInstance()
    {
        //如果对象实例还没有被创建，则创建一个新的实例
        if (self::$_instance == null)
        {
            self::$_instance = new Apc_handle();
        }
        //返回对象实例
        return self::$_instance;
    }

    /**
     * 构造函数
     * 加载mcache的配置文件
     */
    public function __construct() {
		$this->_cObj = new Apc();
        $this->_cacheData   = Config::get(Null, 'cache');
        $this->_cache       = array();
		
    }
	
    public function getData($cacheName,$keyIndex='') {
        $configIndex = $cacheName;
        $cacheIndex = $keyIndex;
        if (array_key_exists($configIndex, $this->_cacheData)) {
            $c = $this->_cacheData[$configIndex];
			if(is_numeric($cacheIndex) || $cacheIndex){
				$cache = $this->_cObj->load($configIndex,$cacheIndex);
				$this->_cache[$configIndex][$cacheIndex] = $cache;
			}else{
				$cache = $this->_cObj->load($configIndex);
				$this->_cache[$configIndex] = $cache;
			}
        }else{
            Out(0, "Cache [{$cacheName}] no config.");
            return false;
        }
        if(!is_numeric($cache) && $cache == false) {
            $result = db()->fetchAll($c['sql']);
            if($result){
	            foreach( $result as $rowkey => $row ) {
	                $value = $c['value'] && isset($row[$c['value']]) ? $row[$c['value']] : $row;
	                unset($cindexArray);
	                // 默认值
	                if (! $c['index'] ||
	                    (strpos($c['index'], ',') === false && ! isset($row[$c['index']])) ||
	                    (strpos($c['index'], ',') === false && $row[$c['index']] == null)) {
	                    $c['index'] = $rowkey;
	                    $cindexArray[] = $c['index'];
	                } else {
	                	$cindexArray = explode(',', $c['index']);
	                    foreach($cindexArray as $cindexKey => &$cindexValue) {
	                        $cindexValue = trim($cindexValue);
	                        $cindexValue = $row[$cindexValue];
	                        if (! isset($cindexValue) || $cindexValue == null)
	                            unset($cindexArray[$cindexKey]);
	                    }
	                }
	                $this->_makeArray($cache, $cindexArray, $value, $c['array']);
	            }
            }
            // 写原有文件
            $this->setData($configIndex, $cache);
            $this->_cache[$configIndex] = $cache;
        }
        return is_numeric($cacheIndex) || $cacheIndex ? $this->_cache[$configIndex][$cacheIndex] : $this->_cache[$configIndex];
    }
	
	
	
	
    private function _makeArray(&$a, $keyArray, $lastValue, $lastArray = false) {
        if (count($keyArray) <= 0) {
            if ($lastArray)
                $a[] = $lastValue;
            else
                $a = $lastValue;
            return;
        }
        $key = array_shift($keyArray);
        if (! $a[$key])
            $a[$key] = array();
        $this->_makeArray($a[$key], $keyArray, $lastValue, $lastArray);
    }

    /**
     * 手动添加cache
     *
     * @access  public
     * @param   string  $file           cache名称
     * @param   mixed   $cache          cache内容
     * @param   string  $cate           cache类型，redis||file
     * @param   int     $expiration     有限时间
     * @return  bool
     */
    public function setData($file, $cache) {
        
        if ( ! $cache ) return false;

        $configIndex = $file;

        if ( ! array_key_exists($configIndex, $this->_cacheData)) {
            Out(0, "Cache [{$cacheName}] no config.");
            return false;
        }
        $c = $this->_cacheData[$configIndex];
		$this->_cObj->save($file,$cache);
        return true;
    }

    /**
     * 删除cache
     *
     * @access  public
     * @param   string  $file   cache句柄
     * @return  bool    返回bool
     */
    public function removeData($file) {
        if (array_key_exists($file, $this->_cacheData)) {
            $c = $this->_cacheData[$file];
			$this->_cObj->remove($file);
			
        }else {
            Out(0, "Cache [{$cacheName}] no config, Remove failed.");
        }
    }

    /**
     * 删除所有的cache
     *
     * @access  public
     * @return  void
     */
    public function clean() {
        while ( list($key, $val) = @each($this->_cacheData) ) {
            $this->removeData($key);
        }
    }
    
	public function __destruct(){}
}
