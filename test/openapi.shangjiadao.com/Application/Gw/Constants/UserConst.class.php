<?php
namespace Gw\Constants;

class UserConst
{
  // 用户状态：有效
  const STATUS_VALID = 1;

  // 用户状态：无效
  const STATUS_INVALID = 0;

  // 验证码类型：注册
  const TYPE_REGISTER = 1;

  // 验证码类型：忘记密码
  const TYPE_FORGET_PASSWORD = 2;

  // 验证码类型：提现
  const TYPE_WITHDRAW = 3;

  // 验证码类型：结算
  const TYPE_SETTLE = 7;

  // 相同IP限制每天请求次数
  const IP_LIMIT_COUNT = 50;

  // 相同号码限制每天请求次数
  const MOBILE_LIMIT_COUNT = 5;

  // 账户类型： 支付宝
  const USER_ACCOUNT_ALIPAY = 1;

  // 账户类型： 银行卡
  const USER_ACCOUNT_BANK = 2;

}
