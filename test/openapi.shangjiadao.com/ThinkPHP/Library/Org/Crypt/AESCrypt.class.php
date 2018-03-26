<?php
namespace Org\Crypt;

class AESCrypt
{
  // 初始化密钥
  private $_common_key = '';
  // 向量
  const KEY_IV = "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00";
  // 密钥长度
  const KEY_LENGTH = 32;

  /**
   * 构造方法
   * @param string $encode_key 密钥
   */
  public function __construct($encode_key)
  {
    self::setCommonKey($encode_key);
  }

  /**
   * 设置密钥
   * @param string $encode_key 密钥
   */
  public function setCommonKey($encode_key)
  {
    $this->_common_key = $encode_key;
  }

  /**
   * 获取密钥
   * @return string 密钥
   */
  public function getCommonKey()
  {
    return $this->_common_key;
  }

  /**
   * 加密
   * @param  string $data 明文
   * @return string       密文
   */
  public function encrypt($data)
  {
    // 打开加密算法与模式
    $td = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_CBC, '');
    // 获取初始化向量长度
    $ks = mcrypt_enc_get_iv_size($td);
    // 获取密钥并验证
    $encode_key = base64_decode(self::getCommonKey());
    if (strlen($encode_key) !== self::KEY_LENGTH) throw new \Exception('The key length is wrong');
    // 初始化加密
    mcrypt_generic_init($td, $encode_key, self::KEY_IV);
    // 填充
    $pad = $ks - (strlen($data) % $ks);
    $data .= str_repeat(chr($pad), $pad);
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
   * @param  string $enc_data 密文
   * @return string           明文
   */
  public function decrypt($enc_data)
  {
    // 加密数据为空
    if (empty($enc_data)) return '';
    // 加密数据格式化
    $enc_data = base64_decode(str_replace("*", "=", $enc_data));
    // 打开加密算法与模式
    $td = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_CBC, '');
    // 获取密钥并验证
    $encode_key = base64_decode(self::getCommonKey());
    if (strlen($encode_key) !== self::KEY_LENGTH) throw new \Exception('The key length is wrong');
    // 初始化加密
    mcrypt_generic_init($td, $encode_key, self::KEY_IV);
    // 解密
    $dec_data = mdecrypt_generic($td, $enc_data);
    // 去除填充
    $pad_count = ord(substr($dec_data, -1));
    $data = substr($dec_data, 0, strlen($dec_data) - $pad_count);
    // 清理缓冲区并且关闭加密模块
    mcrypt_generic_deinit($td);
    mcrypt_module_close($td);
    // 返回解密数据
    return $data;
  }
}