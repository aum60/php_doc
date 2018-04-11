<?php
/**
 * 获取ISO8601格式的时间
 * @param  int $time 时间戳
 * @return string $expiration 时间
 */
function gmt_iso8601($time) {
    $dtStr = date("c", $time);
    $mydatetime = new DateTime($dtStr);
    $expiration = $mydatetime->format(DateTime::ISO8601);
    $pos = strpos($expiration, '+');
    $expiration = substr($expiration, 0, $pos);
    return $expiration."Z";
}

/**
 * 判断是否为日期格式
 * @param  string $time 时间字符串
 * @return boolean 
 */
function is_date($time) {
    return strtotime($time);
}

/**
 * 判断是否为合法的手机号码
 * @param  string $mobile 手机号码
 * @return boolean 
 */
function is_mobile($mobile) {
    return preg_match('/^1[34578]{1}[0-9]{9}$/',$message);
}

/** 
* 数组分页函数
* $listrows     每页多少条数据 
* $page         当前第几页 
* $array        查询出来的所有数组 
* order         0 - 不变   1- 反序 
*/
function page_array($listrows, $page, $array, $order = 0){  
    $page = (empty($page)) ? '1' : $page;   
    $start = ($page-1)*$listrows;

    if($order == 1) {  
      $array = array_reverse($array);  
    }

    $totals = count($array);    
    $countpage = ceil($totals/$listrows);
    $pagedata = array();  
    $pagedata = array_slice($array, $start, $listrows);  

    return $pagedata;   
}

/** 
* 根据域名获取微信配置
* $type  access | signature
*/
function get_wechat_by_srvname($type = 'access') {
    $backup_wechats = C('WECHAT.BACKUPS');
    $access_domain_maps = C('WECHAT.ACCESS_DOMAIN_MAPS');
    $signature_domain_maps = C('WECHAT.SIGNATURE_DOMAIN_MAPS');
    $srv_name = $_SERVER['SERVER_NAME'];
    $wechat_id = null;
    $srv_names = explode('.', $srv_name);

    // 如果带有数字，直接过滤到二级目录
    if (ctype_digit($srv_names[0])) {
        unset($srv_names[0]);
        $srv_name = implode('.', $srv_names);
    }

    if ($type == 'access') {
        $wechat_id = isset($access_domain_maps[$srv_name]) ? $access_domain_maps[$srv_name] : null;
    } elseif ($type == 'signature') {
        $wechat_id = isset($signature_domain_maps[$srv_name]) ? $signature_domain_maps[$srv_name] : null;
    }

    return $wechat_id && isset($backup_wechats[$wechat_id]) ? $backup_wechats[$wechat_id] : null;
} 

/** 
* 根据域名获取微信类库别名
*/
function get_wxpay_libs_by_srvname($function_path) {
    $wxpay_domain_libs_maps = C('WECHAT.WXPAY_DOMAIN_LIBS_MAPS');
    $srv_name = $_SERVER['SERVER_NAME'];

    return isset($wxpay_domain_libs_maps[$srv_name]) ? $wxpay_domain_libs_maps[$srv_name].'.'.$function_path : null;
}

/** 
* 根据下单单号 获取 微信类库别名
*/
function get_wxpay_libs_by_tradeno($tradeno, $function_path) {
    $wxpay_mchid_libs_maps = C('WECHAT.WXPAY_MCHID_LIBS_MAPS');
    $mch_id = substr($tradeno, 0, 10);

    return isset($wxpay_mchid_libs_maps[$mch_id]) ? $wxpay_mchid_libs_maps[$mch_id].'.'.$function_path : null;
}

/**
 * 根据域名来火雨微信支付回调地址
 */
function get_wxpay_notify_url_by_srvname($target) {
    $wxpay_notify_domain_maps = C('WECHAT.WXPAY_NOTIFY_DOMAIN_MAPS');
    $srv_name = $_SERVER['SERVER_NAME'];
    $http = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' ? 'https://' : 'http://';
    $url = $http . $_SERVER['HTTP_HOST'];

    if (!isset($wxpay_notify_domain_maps[$srv_name])) {
        return null;
    }
    $lib_name = $wxpay_notify_domain_maps[$srv_name];

    return $url.'/api/wxpay_notify/'.$lib_name."/".$target;
}


/**
 * 加密openid 
 * @param string $openid
 * @return string encryption 
 * */ 
function encrypt_check_openid($openid, $encryption) {
    $return = false;
    $prefix = 'sjdecopenid';
    $crypt = new \Org\Crypt();
    list($dec_result, $decryption) = $crypt->decrypt($encryption);
    if ($dec_result && $decryption == $prefix.$openid) $return = true;
    return $return;
}
