<?php
namespace Gw\Constants;

class UserAccountFlowsConst
{
  // 流水类型： 活动结算
  const ACTION_ACTIVITY_SETTLED = 1;

  // 流水类型： 邀请注册返佣
  const ACTION_INVITE_USER_RETURN = 2;

  // 流水类型： 提现
  const ACTION_WITHDRAWALS = 3;
  
  // 是否流出
  const IS_OUTFLOW = 1;
}
