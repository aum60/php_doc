<?php
// 设置时区
date_default_timezone_set('Asia/Shanghai');

return array(
  'LOAD_EXT_CONFIG' => 'error_code,db',

  'MODULE_ALLOW_LIST' => array(
    'Gw',
  ),

  'URL_MODEL' => '3', 
  // 0 (普通模式); 1 (PATHINFO 模式); 2 (REWRITE 模式);
  // 3 (兼容模式)默认为PATHINFO 模式，提供最好的用户体验和SEO支持

  'URL_CASE_INSENSITIVE' => false, // url不区分大小写

  'COOKIE_PREFIX' => 'sjd_',  // cookie前缀

  'CRYPT_TYPE' => 'DES', // 目前支持AES，DES两种加密方式

  // 密钥
  "DES_KEY" => "YmEwYTZkZGQNCmQ1NTY2OTgyDQphMTgxYTYwMw0K",
  "AES_KEY" => "NRTz2wvKvlXJEuDXfz5ydYfLiK1snSGOV5hvZ0aprBE=",

  // token有效期（30天）
  'TOKEN_VALIDITY_PERIOD' => 2592000,

  //支付token后缀
  'PAY_TOKEN_POSTFIX' => 'shangjiadao',

  // 活动支付结算扣点
  'PAY_CHARGE_RATE' => '0.025',

  // 错误页面
  'ERROR_PAGE' => '/404.html',

  // session

  // 缓存开关
  'DATA_CACHE_TIME'       => 0,      // 数据缓存有效期 0表示永久缓存
  'DATA_CACHE_COMPRESS'   => false,   // 数据缓存是否压缩缓存
  'DATA_CACHE_CHECK'      => false,   // 数据缓存是否校验缓存
  'DATA_CACHE_TYPE'       => 'Redis',  // 数据缓存类型,
  'DATA_CACHE_PREFIX'     => 'sjd', //默认动态缓存为Redis
  'REDIS_RW_SEPARATE'     => false, //Redis读写分离 true 开启
  'REDIS_TIMEOUT'         => '120', //超时时间
  'REDIS_PERSISTENT'      => false, //是否长连接 false=短连接
  'REDIS_HOST'            => '172.18.168.232', //redis服务器ip，多台用逗号隔开；读写分离开启时，第一台负责写，其它[随机]负责读；
  'REDIS_PORT'            => '6379', //端口号
  'REDIS_AUTH'            => 'dnHF_123', //密码
);
