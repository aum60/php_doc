<?php
/**
 * 发送微信模板消息
 */

namespace Gw\Util;

class WxMessageUtil
{
    const TOKEN_URL = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=';
    const SEND_URL = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=';
    
    /**
     * [发送微信模板消息]
     * @param string $openId        微信用户的openId
     * @param string $templateId    模版id
     * @param array $param          发送内容
     * @return int                  0:成功发送， 其他：发送失败
     */
    public static function sendMessageTemplate($openId, $templateId, $param, $detailUrl="http://weixin.qq.com/download") {
        $templateData = [
            'touser'        => $openId,
            'template_id'   => $templateId,
            'url'           => $detailUrl,
            'topcolor'      => "#FF0000",
            'data'          => $param
        ];
        $sendUrl=self::SEND_URL.self::getAccessToken();
        $jsonTemplateData = json_encode($templateData);
        $resultJson=self::httpRequest($sendUrl,$jsonTemplateData);
        $resultJson =  json_decode($resultJson);

        return $resultJson->errcode;
    }

    /**
     * [发送POST请求]
     * @param string $url
     * @param array $data 
     */
    private static function httpRequest($url,$data=array()){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }

    /**
     * [获取微信access_token]
     * @return string
     */
    public static function getAccessToken(){
        $postUrl = self::TOKEN_URL.C('WECHAT.APP_ID')."&secret=".C('WECHAT.APP_SECRET');
        $jsonToken = self::httpRequest($postUrl);
        $accessToken = json_decode($jsonToken, true);

        return $accessToken['access_token'];
    }
}