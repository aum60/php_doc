<?php
/**
 * 验证码控制器
 */

namespace Gw\Controller;

use Gw\Controller\CommonController;
use Think\Log;
use Gw\Constants\UserConst;

// ok openapi 引入
use OK\PhpEnhance\DomainObject\FailureResultDO;
use OK\PhpEnhance\DomainObject\ServiceResultDO;
use OK\PhpEnhance\DomainObject\SuccessResultDO;
use OK\PhalconEnhance\Util\DiUtil;

class VerifyController extends CommonController
{
  /**
   * 生成验证码
   */
  public function code($type)
  {
    $Verify = new \Think\Verify();

    $type = trim($type);
    $Verify->fontSize = 30;
    $Verify->length   = 4;
    $Verify->useNoise = false;
    $Verify->codeSet = '2345678abcdefhijkmnpqrstuvwxyz'; 

    $res = $Verify->entry2base64($type);

    // $session = DiUtil::getSessionService();
    // $session->set('abc', 'hello,world.');
    // file_put_contents('/opt/ok/openapi.shangjiadao.com/Application/Runtime/Logs/Gw/cache.log', $session->get('abc'), FILE_APPEND);

    // session('session_demo', '12344abc');
    // session('session_demo2', ['a' => '1', 'b' => '2']);
    // file_put_contents('/opt/ok/openapi.shangjiadao.com/Application/Runtime/Logs/Gw/cache-2.log', session('session_demo'), FILE_APPEND);
    // file_put_contents('/opt/ok/openapi.shangjiadao.com/Application/Runtime/Logs/Gw/cache-3.log', session('session_demo2'), FILE_APPEND);

    return new SuccessResultDO($res);
  }
}