<?php
namespace Gw\Controller;

use Think\Controller\RestController;
use Think\Controller;

// ok openapi 引入
use OK\PhpEnhance\DomainObject\FailureResultDO;
use OK\PhpEnhance\DomainObject\ServiceResultDO;
use OK\PhpEnhance\DomainObject\SuccessResultDO;

class CommonController extends Controller
{
  // 请求参数
  protected $request = array();

  // 验证成功返回值
  protected $_default = array();

  /**
   * 请求成功的返回方法
   * @param $data
   */
  protected function successReturn($data=null)
  {
    $result['code'] = 0;
    $result['message'] = 'success';
    if (!empty($data)) $result['data'] = $data;
    $this->response($result, 'json');
  }

  /**
   * 请求失败的返回方法
   * @param $code_msg
   */
  protected function failReturn($code_msg, $data=null)
  {
    if (is_array($code_msg)) {
        $result['code'] = intval($code_msg[0]);
        $result['message'] = $code_msg[1];
    } else {
        $result['code'] = 1;
        $result['message'] = $code_msg;
    }
    
    if(!empty($data)) $result['data'] = $data;
    $this->response($result, 'json');
  }

  /**
   * 初始化
   */
  protected function _initialize()
  {
    // if(!IS_CLI && !APP_DEBUG) die('require cli to call');    
  }

  
  /**
   * @param $name
   * @return string
   */
  public function __get($name)
  {
    return isset($this->_default[$name]) ? $this->_default[$name] : '';
  }

  /**
   * @param $name
   * @param $value
   * @return mixed
   */
  public function __set($name, $value)
  {
    return $this->_default[$name] = $value;
  }

  /**
   * 校验商家是否已经到期
   */
  protected function checkUserExpired($user_id) {
    $cache_key = "user_cache_expire_time_{$user_id}";
    $cache_time = 3600*24*30;
    if(!($user = S($cache_key))) {
      $user = M('Users')->where(['id'=>$user_id])->field('expire_end,mobile')->find();
      if ($user && $user['expire_end'] <= time()) {
        return $user;
      }

      S($cache_key, $user, $cache_time);
    }

    if ($user && $user['expire_end'] <= time()) {
      return $user;
    }

    return false;
  }
}
