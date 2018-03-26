<?php
namespace Org;
/**
 * API filter 类
 *
 */
class Filter
{
  const MUST_VALIDATE = 1;      // 必须验证
  const EXISTS_VALIDATE = 0;      // 存在字段则验证,默认值
  const VALUE_VALIDATE = 2;      // 字段值不为空则验证,这种情况则允许用户输入空值


  /**
   * [__construct]
   */
  public function __construct()
  {

  }

  /**
   * 使用正则验证数据
   * @access public
   * @param string $value 要验证的数据
   * @param string $rule 验证规则
   * @return boolean
   */
  static public function regex($value, $rule)
  {
    $validate = array(
      'require' => '/\S+/',
      'email' => '/^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/',
      'url' => '/^http(s?):\/\/(?:[A-za-z0-9-]+\.)+[A-za-z]{2,4}(?:[\/\?#][\/=\?%\-&~`@[\]\':+!\.#\w]*)?$/',
      'currency' => '/^\d+(\.\d+)?$/',
      'number' => '/^\d+$/',
      'zip' => '/^\d{6}$/',
      'integer' => '/^[-\+]?\d+$/',
      'double' => '/^[-\+]?\d+(\.\d+)?$/',
      'english' => '/^[A-Za-z]+$/',
    );
    // 检查是否有内置的正则表达式
    if (isset($validate[strtolower($rule)]))
      $rule = $validate[strtolower($rule)];
    return preg_match($rule, $value) === 1;
  }

  /**
   * API验证类
   * @access protected
   * @param array $data 创建数据
   * @param string $type 创建类型
   * @return boolean
   */
  static public function autoValidation($data, $validate)
  {
    //验证成功返回值
    $default = array();
    // 属性验证
    foreach ($validate as $key => $val) {
      // 初始化验证结果
      $res = '';

      // 验证因子定义格式
      // array(field,rule,message,condition,type,default,params)
      if (0 == strpos($val[2], '{%') && strpos($val[2], '}'))
        // 支持提示信息的多语言 使用 {%语言定义} 方式
        $val[2] = L(substr($val[2], 2, -1));

      $val[3] = isset($val[3]) ? $val[3] : self::EXISTS_VALIDATE;
      $val[4] = isset($val[4]) ? $val[4] : 'regex';

      // 判断验证条件
      switch ($val[3]) {
        case self::MUST_VALIDATE:   // 必须验证
          $res = self::_validationFieldItem($data, $val);
          break;
        case self::VALUE_VALIDATE:    // 值不为空的时候才验证
                    if('' != trim($data[$val[0]]))
                      $res = self::_validationFieldItem($data, $val);
                    break;
        default:    // 存在该字段就验证
          if (isset($data[$val[0]])) {
            $res = self::_validationFieldItem($data, $val);
          } else {
            isset($val[5]) && $default[$val[0]] = $val[5];
          }
      }

      // 支持函数返回值
      if ($res === true) {
        $default[$val[0]] = $data[$val[0]];
      } elseif ($res === false) {
        return $val[2];
      } elseif($res !== '') {
        $default[$val[0]] = $res;
      }
    }

    return $default;
  }


  /**
   * 根据验证因子验证字段
   * @access protected
   * @param array $data 创建数据
   * @param array $val 验证因子
   * @return boolean
   */
  static protected function _validationFieldItem($data, $val)
  {
    switch (strtolower(trim($val[4]))) {
      case 'function':// 使用函数进行验证
      case 'callback':// 调用方法进行验证
        $args = isset($val[6]) ? (array)$val[6] : array();
        if (is_string($val[0]) && strpos($val[0], ','))
          $val[0] = explode(',', $val[0]);
        if (is_array($val[0])) {
          // 支持多个字段验证
          foreach ($val[0] as $field)
            $_data[$field] = $data[$field];
          array_unshift($args, $_data);
        } else {
          array_unshift($args, $data[$val[0]]);
        }
        if ('function' == $val[4]) {
          return call_user_func_array($val[1], $args);
        } else {
                   return call_user_func_array(array(&$this, $val[1]), $args);
                }
      case 'confirm': // 验证两个字段是否相同
        return $data[$val[0]] == $data[$val[1]];
      default:  // 检查附加规则
        return self::check($data[$val[0]], $val[1], $val[4]);
    }
  }

  /**
   * 验证数据 支持 in between equal length regex expire ip_allow ip_deny
   * @access public
   * @param string $value 验证数据
   * @param mixed $rule 验证表达式
   * @param string $type 验证方式 默认为正则验证
   * @return boolean
   */
  static protected function check($value, $rule, $type = 'regex')
  {
    $type = strtolower(trim($type));
    switch ($type) {
      case 'in': // 验证是否在某个指定范围之内 逗号分隔字符串或者数组
      case 'notin':
        $range = is_array($rule) ? $rule : explode(',', $rule);
        if ($type == 'in') {
          return array_diff(explode(",", $value), $range) ? false : true;
        } else {
          return array_intersect($range, explode(",", $value)) ? false : true;
        }
      case 'between': // 验证是否在某个范围
      case 'notbetween': // 验证是否不在某个范围
        if (is_array($rule)) {
          $min = $rule[0];
          $max = $rule[1];
        } else {
          list($min, $max) = explode(',', $rule);
        }
        return $type == 'between' ? $value >= $min && $value <= $max : $value < $min || $value > $max;
      case 'equal': // 验证是否等于某个值
      case 'notequal': // 验证是否等于某个值
        return $type == 'equal' ? $value == $rule : $value != $rule;
      case 'length': // 验证长度
        $length = mb_strlen($value, 'utf-8'); // 当前数据长度
        if (strpos($rule, ',')) { // 长度区间
          list($min, $max) = explode(',', $rule);
          return $length >= $min && $length <= $max;
        } else {// 指定长度
          return $length == $rule;
        }
      case 'expire':
        list($start, $end) = explode(',', $rule);
        if (!is_numeric($start)) $start = strtotime($start);
        if (!is_numeric($end)) $end = strtotime($end);
        return NOW_TIME >= $start && NOW_TIME <= $end;
      case 'ip_allow': // IP 操作许可验证
        return in_array(get_client_ip(), explode(',', $rule));
      case 'ip_deny': // IP 操作禁止验证
        return !in_array(get_client_ip(), explode(',', $rule));
      case 'regex':
      default:    // 默认使用正则验证 可以使用验证类中定义的验证名称
        // 检查附加规则
        return self::regex($value, $rule);
    }
  }

}
