<?php
namespace Gw\Util;

class UrlUtil
{
  public static function currentUrl()
  {
      $http = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' ? 'https://' : 'http://';
      $url = $http . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

      return $url;
  }
}
