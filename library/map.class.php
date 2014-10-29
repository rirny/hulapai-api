<?php
class Map
{

	/*
	$ak = 'TjSgo1GXWqa73bjMXcSwIAXP';
	$url = 'http://api.map.baidu.com/location/ip';
	$ip = '42.120.22.28';
	ip	ip地址	string	可选，ip不出现，或者出现且为空字符串的情况下，会使用当前访问者的IP地址作为定位参数
	ak	用户密钥	string	必选，在lbs云官网注册的access key，作为访问的依据
	coor	输出的坐标格式	string	可选，coor不出现时，默认为百度墨卡托坐标；coor=bd09ll时，返回为百度经纬度坐标
	$coor = 'bd09ll';
	*/

	private static $_ak = 'TjSgo1GXWqa73bjMXcSwIAXP';
	private static $_api= 'http://api.map.baidu.com';
	private static $_coordtype= 'bd09ll';	
	private static $_output = 'json';
	

	// ip获取经纬度
	// http://api.map.baidu.com/location/ip?ak=F454f8a5efe5e577997931cc01de3974&ip=202.198.16.3&coor=bd09ll
	public static function getCoordsFromIp($ip)
	{
		$query = array(
			'ip' => $ip,
			'ak' => self::$_ak,
			'coor' => self::$_coordtype,
			'output' => self::$_output
		);
		$queryString = self::$_api .'/location/ip?'. http_build_query($query);
		$response = file_get_contents($queryString);
		$response = json_decode($response, true);
		if($response['status'] == 0)
		{
			return array_values($response['content']['point']);
		}
		return false;
	}
	// 地址获取经纬度
	public static function getCoordsFromAddress($city, $address)
	{
		$api = self::$_api . "/geocoder/v2/?";
		$ak = self::$_ak;
		$coordtype = self::$_coordtype;
		$output = self::$_output;
		$queryString = $api . http_build_query(compact('city', 'address', 'output', 'ak', 'pois', 'location', 'output'));
		$params = parse_url($queryString);		
		$param = parse_str($params['query'], $query);		
		$response = file_get_contents($queryString);		
		$response = json_decode($response, true);
		if($response['status'] == 0)
		{
			return array_values($response['result']['location']);
		}
		return false;
	}	
	// 百度经转换GPS纬度
	public static function baiduToGpsXY($lng, $lat)
	{
		$Baidu_Server = "http://api.map.baidu.com/ag/coord/convert?from=0&to=4&x={$lat}&y={$lng}";
		$result = @file_get_contents($Baidu_Server);
		$json = json_decode($result);		
		if($json->error == 0)
		{
			$bx = base64_decode($json->x);
			$by = base64_decode($json->y);
			$GPS_x = 2 * $x - $bx;
			$GPS_y = 2 * $y - $by;
			return $GPS_x.','.$GPS_y;//经度,纬度
		}
		return false;
	}

	//
	/* 坐标转换
	 * $coords 源坐标 格式：经度,纬度;经度,纬度…
	 * from 
		 1：GPS设备获取的角度坐标;
		 2：GPS获取的米制坐标、sogou地图所用坐标;
		 3：google地图、soso地图、aliyun地图、mapabc地图和amap地图所用坐标
		 4：3中列表地图坐标对应的米制坐标
		 5：百度地图采用的经纬度坐标
		 6：百度地图采用的米制坐标
		 7：mapbar地图坐标;
		 8：51地图坐标
	* to 
		5：bd09ll(百度经纬度坐标),
		6：bd09mc(百度米制经纬度坐标);
		6：bd09mc(百度米制经纬度坐标);
	* api = '/geoconv/v1/?';
	*/
	function geoconv($coords=Array(), $from=1, $to=5)
	{
		$queryString = $api . http_build_query(array(
			'coords' => join(";", $coords),
			'ak' => self::$_ak,
			'from' => $from,
			'to' => $to,
			'output' => self::$_output,
		));
		$params = parse_url($queryString);		
		$param = parse_str($params['query'], $query);		
		$response = file_get_contents($queryString);
		return json_decode($response, true);
	}

	// 距离计算经纬度搜索范围	
	public static function getAround(float $lng, float $lat, int $raidus)
	{   
		$result = array();
        $degree = (24901*1609)/360.0; 
        $dpmLat = 1/$degree;
        $radiusLat = $dpmLat * $raidus;          
		$result['lat'] = array($lat - $radiusLat, $lat + $radiusLat);
        $mpdLng = $degree * cos($lat * (PI/180));		
        $dpmLng = 1 / $mpdLng; 
        $radiusLng = $dpmLng * $raidus;
		$result['lat'] = array($lng - $radiusLng, $lng + $radiusLng);
		return $result;
    } 

	// 百度经纬度范围
	/*
	 * $point = (lng, lat) //
		lng X坐标 1000米范围系数 ±0.010520 经度
		lat Y坐标 1000米范围系数 ±0.009000 纬度
	 * raidus 半径(/1000) * 系数
	*/
	public static function getBaiduAround($point, $raidus = 1000)
	{
		if(empty($point)) return false;
		if(is_array($point)) 
		{
			list($lng, $lat) = $point;
		}else{
			list($lng, $lat) = explode(";", $point);
		}
		$radiusLat = $raidus / 1000 * 0.009000;
		$result['lat'] = array(
			'min' => $lat - $radiusLat, 
			'max' => $lat + $radiusLat
		);
		$radiusLng = $raidus / 1000 * 0.010520;
		$result['lng'] = array(
			'min' => $lng - $radiusLng, 
			'max' => $lng + $radiusLng
		);
		return $result;
	}

	public static function getAroundQuery($arround = array())
	{
		if(empty($arround['lat']) || $arround['lng']) return ;
		list($latMin, $latMax) = array_values($arround['lat']);
		list($lngMin, $lngMax) = array_values($arround['lng']);
		if($latMin >= $latMax)
		{
			$result['lat,>='] = $latMin;
			$result['lat,<'] = $latMax;
		}
		if($lngMin >= $lngMax)
		{
			$result['lng,>='] = $lngMin;
			$result['lng,<'] = $lngMax;
		}
		return $result;
	}
	
	const EARTH_RADIUS = 6370693.5;
	const M_PI2 = 6.28318530712; // 2*PI
	const M_PI_180 = 0.01745329252; // PI/180.0
	
	public static function getShortDistance($point, $coord)
	{
		list($lon1, $lat1) = $point;
		list($lon2, $lat2) = $coord;
		// 角度转换为弧度
		$ew1 = $lon1 * self::M_PI_180;
		$ns1 = $lat1 * self::M_PI_180;
		$ew2 = $lon2 * self::M_PI_180;
		$ns2 = $lat2 * self::M_PI_180;
		// 经度差
		$dew = $ew1 - $ew2;
		// 若跨东经和西经180 度，进行调整
		if ($dew > M_PI)
			$dew = self::M_PI2 - dew;
		else if ($dew < - M_PI)
			$dew = self::M_PI2 + $dew;
		$dx = self::EARTH_RADIUS * cos($ns1) * $dew; // 东西方向长度(在纬度圈上的投影长度)
		$dy = self::EARTH_RADIUS * ($ns1 - $ns2); // 南北方向长度(在经度圈上的投影长度)
		// 勾股定理求斜边长
		$distance = sqrt($dx * $dx + $dy * $dy);
		return $distance;
	}

	public static function getLongDistance($point, $coord)
	{
		list($lon1, $lat1) = $point;
		list($lon2, $lat2) = $coord;
		// 角度转换为弧度
		$ew1 = $lon1 * self::M_PI_180;
		$ns1 = $lat1 * self::M_PI_180;
		$ew2 = $lon2 * self::M_PI_180;
		$ns2 = $lat2 * self::M_PI_180;
		// 求大圆劣弧与球心所夹的角(弧度)
		$distance = sin($ns1) * sin($ns2) + cos($ns1) * cos($ns2) * cos($ew1 - $ew2);
		// 调整到[-1..1]范围内，避免溢出
		if ($distance > 1.0)
			 $distance = 1.0;
		else if ($distance < -1.0)
			  $distance = -1.0;
		// 求大圆劣弧长度
		$distance = self::EARTH_RADIUS * acos($distance);
		return $distance;
	}




}