<?php
namespace Gw\Constants;

class UserInviteConst
{
  // 邀请状态：待充值
  const STATUS_TO_BE_CHARGED = 1;

  // 邀请状态：待结算
  const STATUS_TO_BE_SETTLED = 2;

  // 邀请状态：已结算
  const STATUS_HAS_BEEN_SETTLED = 3;

  // 邀请注册返点
  const BE_INVITED_RETURN_POINT = 0.05;

  // 邀请注册返点结算冻结周期: 天
  const SETTLED_FREEZE_DAYS = 7;

  // 邀请有效期：用户注册时间为标准（以天计）
  const INVITE_EXPIRE_DAYS = 3;
}
