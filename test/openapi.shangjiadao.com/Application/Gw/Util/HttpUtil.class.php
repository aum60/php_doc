<?php
namespace Gw\Util;

class HttpUtil
{
  /**
   * http curl
   * @param  string $url    url
   * @param  string $method method 
   * @param  array $data    data
   * @param  array $header header
   * @param  array $option  option
   * @return string $response response
   */
  public static function curl($url, $method = 'GET', $data = null, $header = null, $option = null)
  {
    $ch = curl_init();

    $options = array(
      CURLOPT_URL => $url,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_FAILONERROR => false
    );
    is_array($option) && $options = array_merge($options, $option);
    curl_setopt_array($ch, $options);

    if(strlen($url) > 5 && strtolower(substr($url,0,5)) == "https" ) {
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    }

    if($header) curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

    switch (strtoupper($method)) {
      case 'GET':
        curl_setopt($ch, CURLOPT_HTTPGET, true);
        break;
      case 'POST':
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        break;
      case 'PUT':
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        break;
      case 'DELETE':
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        break;
    }

    $response = curl_exec($ch);

    if (curl_errno($ch)) throw new \Exception(curl_error($ch), curl_errno($ch));

    curl_close($ch);

    return $response;
  }
}