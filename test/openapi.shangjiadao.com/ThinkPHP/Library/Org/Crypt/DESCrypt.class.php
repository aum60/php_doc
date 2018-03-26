<?php
namespace Org\Crypt;

class DESCrypt
{
  // 初始化密钥
  private $_common_key = '';
  // 向量
  const KEY_IV = "\x00\x00\x00\x00\x00\x00\x00\x00";
  
  /**
   * @param $encode_key 密钥
   */
  public function __construct($encode_key)
  {
    self::setCommonKey($encode_key);
  }
  
  /**
   * @param $encode_key 密钥
   */
  public function setCommonKey($encode_key)
  {
    $this->_common_key = $encode_key;
  }
  
  /**
   * @param $encode_key 密钥
   */
  public function getCommonKey()
  {
    return $this->_common_key;
  }
  
  /**
   * 加密
   * 
   * @param $data 明文
   * @return $enc_data 密文
   */
  public function encrypt($data)
  {
    // 打开加密算法与模式
    $td = mcrypt_module_open(MCRYPT_3DES, '', MCRYPT_MODE_CBC, '');
    // 获取初始化向量长度
    $ks = mcrypt_enc_get_iv_size($td);
    // 验证向量长度
    if (strlen(self::KEY_IV) !== $ks) throw new \Exception('The iv length is wrong');
    // 解析密钥
    $decode_array = split("\r\n", base64_decode(self::getCommonKey()));
    array_pop($decode_array);
    // 验证密钥格式
    if (count($decode_array) == 3) {
      foreach ($decode_array as $key => $val) {
        $key_size = strlen($val);
        if ($key_size === 0 || $key_size > $ks) {
          throw new \Exception('The key length is wrong');
        }
      }
    } else {
      throw new \Exception('The key format is wrong');
    }
    // 合成密钥
    $encode_key = $decode_array[0] . $decode_array[1]. $decode_array[2];
    // 初始化加密
    mcrypt_generic_init($td, $encode_key, self::KEY_IV);
    // 加密
    $crypt_text = mcrypt_generic($td, $data);
    // 清理缓冲区并且关闭加密模块
    mcrypt_generic_deinit($td);
        mcrypt_module_close($td);
        // 处理加密数据
        $enc_data = str_replace("=","*",base64_encode($crypt_text));
        return $enc_data;
  }
  
  /**
   * 解密
   * 
   * @param $enc_data 密文
   * @return $dec_data 明文
   */
  public function decrypt($enc_data)
  {
    // 密文为空
    if (empty($enc_data)) return '';
    // 加密数据格式化
    $data = base64_decode(str_replace("*","=",$enc_data));
    // 打开加密算法与模式
    $td = mcrypt_module_open(MCRYPT_3DES, '', MCRYPT_MODE_CBC, '');
    // 获取初始化向量长度
    $ks = mcrypt_enc_get_iv_size($td);
    // 解析密钥
    $decode_array = split("\r\n", base64_decode(self::getCommonKey()));
    array_pop($decode_array);
    // 验证秘钥格式
    if (count($decode_array) == 3) {
      foreach ($decode_array as $key => $val) {
        $key_size = strlen($val);
        if ($key_size === 0 || $key_size > $ks) {
          throw new \Exception('The key length is wrong.');
        }
      }
    } else {
      throw new \Exception('The key format is wrong.');
    }
    // 合成密钥
    $encode_key = $decode_array[0] . $decode_array[1]. $decode_array[2];
    // 初始化加密
    mcrypt_generic_init($td, $encode_key, self::KEY_IV);
    // 解密
    $dec_data = mdecrypt_generic($td, $data);
    // 清理缓冲区并且关闭加密模块
    mcrypt_generic_deinit($td);
    mcrypt_module_close($td);
    // 返回解密数据
    return rtrim($dec_data, "\0");
  }
}