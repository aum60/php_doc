<?php

/*
 * 执行任务的入口
 * 访问方法：http://localhost/api/cli.php/?m=cron&c=index&a=index
 */

namespace Gw\Controller;

use Think\Log;
use Gw\Controller\CommonController;

class IndexController extends CommonController
{
  public function index()
  {

    echo 'ok';
    die;    
  }
}