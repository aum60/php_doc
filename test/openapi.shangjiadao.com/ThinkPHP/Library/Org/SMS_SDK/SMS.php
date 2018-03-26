<?php
include "TopSdk.php";
date_default_timezone_set('Asia/Shanghai');

class SMS
{
  private $client = null;

  private $appKey = '';

  private $secretKey = '';

  private $template_code = [];

  public function __construct()
  {
    // 初始化请求对象
    $this->client = new TopClient;
    $this->client->appkey = '23359651';
    $this->client->secretKey = 'b288368617e6f301edfdc54e8d06ff5e';

    // 初始化短信模版
    $this->template_code = array(
      '1' => 'SMS_8960231',
      '2' => 'SMS_8960229',
      '3' => 'SMS_8960235',
      '4' => 'SMS_31095116',
      '5' => 'SMS_41795031',
      '6' => 'SMS_45325052',
      '7' => 'SMS_8960235',  // 商家活动结算验证码
      '8' => 'SMS_55090061', // 提现处理成功提示
    );
  }

  /**
   * 发送短信
   * @param  string $mobile 手机号码
   * @param  string $type   发送类型(1:注册 2:忘记密码 3:提现 4:通过注册申请)
   * @param  string $code   验证码
   * @return json   $res    返回结果
   */
  public function sendMsg($mobile, $type, $code)
  {
    $req = new AlibabaAliqinFcSmsNumSendRequest;
    $req->setSmsType("normal");
    $req->setSmsFreeSignName("商家岛");
    $req->setSmsParam("{\"code\":\"{$code}\",\"product\":\"商家岛\"}");
    $req->setRecNum($mobile);
    $template_code = $this->template_code[$type];
    $req->setSmsTemplateCode($template_code);
    $res = $this->client->execute($req);
    return $res;
  }
}
