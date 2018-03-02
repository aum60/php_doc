<?php
namespace Gw\Constants;

class LotteryWinningConst
{
  // 中奖状态：有效
  const STATUS_VALID = 1;

  // 中奖状态：无效（比如中奖记录被删除）
  const STATUS_INVALID = 0;
  
  // 活动是否兑奖：已兑
  const ALREADY_EXCHANGED = 1;

  // 活动是否兑奖：未兑
  const NOT_EXCHANGED = 0;
}
