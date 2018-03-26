<?php
/**
 * 工具 控制器
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

class UtilController extends CommonController
{
  /**
   * 生成session_id
   */
  public function session_id()
  {
    return new SuccessResultDO(session_id());
  }
}