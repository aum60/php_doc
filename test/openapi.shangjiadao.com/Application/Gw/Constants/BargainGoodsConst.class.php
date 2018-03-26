<?php

namespace Gw\Constants;

class BargainGoodsConst
{
    // 活动状态：有效
    const STATUS_VALID = 1;

    // 活动状态：无效
    const STATUS_INVALID = 0;
    
    // 提现
    const ALLOW_WITHDRAW = 1;

    const NOT_ALLOW_WITHDRAW    = 0;
    const GOODS_ON              = 1;//上架
    const PAY_COMPLETE          = 1;//已付款
    const REFUND_BY_JOINERS     =  1;
    const REFUND_BY_EXCEPTION_ORDER =  2;
}
