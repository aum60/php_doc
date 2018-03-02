<?php
namespace Gw\Constants;

class OrderConst
{
  // 订单状态：未付款
  const STATUS_UNPAID = 0;

  // 订单状态：已付款
  const STATUS_PAID = 1;

  // 订单状态：已退款
  const STATUS_REFUNDED = 2;

  // 订单状态：已提现
  const STATUS_WITHDREW = 3;

  // 订单状态：已结算
  const STATUS_SETTLED = 4;

  // 付款方式：无
  const PAYMENT_NONE = 0;

  // 付款方式：微信支付
  const PAYMENT_WXPAY = 1;

  // 付款方式：支付宝支付
  const PAYMENT_ALIPAY = 2;
}
