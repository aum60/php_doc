<?php
namespace Gw\Controller;

use Think\Log;
use Gw\Controller\CommonController;
use Gw\Constants\ActivityConst;
use Gw\Constants\JoinerConst;
use Gw\Util\IDUtils;

// ok openapi 引入
use OK\PhpEnhance\DomainObject\FailureResultDO;
use OK\PhpEnhance\DomainObject\ServiceResultDO;
use OK\PhpEnhance\DomainObject\SuccessResultDO;

class ActivityController extends CommonController
{
  /**
   * 根据投票ID获取活动信息
   * @param  int $joiner_id 参加者ID
   * @return array $activity_info 活动信息
   */
  protected function getActivityInfoByJoinerId($joiner_id)
  {
    // 获取活动ID
    $cache_key = "activity_joiner_cache_{$joiner_id}";
    $cache_time = 3600*24;
    if(!($joiner = S($cache_key))) { 
      $joiner = M('Joiners')->where(['id' => $joiner_id])->field('activity_id,status')->find();

      if (empty($joiner)) {
        $err = C('ACTIVITY_IS_NOT_EXIST');
        return new FailureResultDO($err[0], $err[1]);
      }
      if ($joiner['status'] == JoinerConst::STATUS_INVALID) {
        $err = C('ACTIVITY_JOINER_HAS_BEEN_REMOVED');
        return new FailureResultDO($err[0], $err[1]);
      }

      S($cache_key, $joiner, $cache_time);
    }

    // 获取活动信息
    $activity_info = self::getActivityInfoById($joiner['activity_id']);

    return $activity_info;
  }

  /**
   * 根据ID获取活动信息
   * @param  int $id 活动ID
   * @return array $activity_info 活动信息
   */
  protected function getActivityInfoById($id, $nocache = false)
  {

    $cache_key = "activity_cache_{$id}";
    $cache_time = 300;
    
    if(!($activity_info = S($cache_key)) || $nocache) { 
      // 获取活动信息
      $activity_cond = array(
        'id' => $id,
        //'status' => ActivityConst::STATUS_VALID
      );
      $activity_info = M('Activities')->where($activity_cond)
                        ->field('id,user_id,title,start_time,end_time,rules,prizes,receive_info,' .
          'host_info,host_img,type,share_title,share_content,share_img,share_host,status,others,create_time')->find();

      // 活动不存在
      if (empty($activity_info))  {
        $err = C('ACTIVITY_IS_NOT_EXIST');
        return new FailureResultDO($err[0], $err[1]);
      } else {
        if ($activity_info['status'] == ActivityConst::STATUS_INVALID) {
          if (!($mobile = self::getActivityPrizesMobile($activity_info['prizes']))) {
            $mobile = self::getUserMobile($activity_info['user_id']);
          }
          $err = C('ACTIVITY_IS_NOT_EXIST');
          return new FailureResultDO($err[0], $err[1], array('mobile' => $mobile));
        }
      }

      unset($activity_info['status']);

      S($cache_key, $activity_info, $cache_time);
    }

    //检测用户是否已经到期
    $user = $this->checkUserExpired($activity_info['user_id']);
    if ($user && $activity_info['end_time'] > time()) {
      $err = C('ACTIVITY_IS_NOT_EXIST');
      return new FailureResultDO($err[0], $err[1], array('mobile' => $user['mobile']));
    }

    return $activity_info;
  }

  /**
   * 获取商家联系方式
   * @param  int $user_id 商家ID
   * @return string
   */
  protected function getUserMobile($user_id)
  {
    $cache_key = "activity_user_cache_{$user_id}";
    $cache_time = 2592000;//30天
    if(!($user = S($cache_key))) { 
      $cond = array(
        'id' => $user_id,
      );
      $user = M('Users')->where($cond)->field('mobile')->cache($cache_key, $cache_time)->find();
    }
    
    return $user === false ? '--'  : $user['mobile'];
  }

  /**
   * 获取活动奖项配置联系方式
   * @param string $prizes 奖项设置
   * @return 
   */
  protected function getActivityPrizesMobile ($prizes) {
   
    $prizes = json_decode($prizes, true);
    if (isset($prizes['mobile'])) {
      return $prizes['mobile'];
    }

    if (isset($prizes['receive_prize_mobile'])) {
      return $prizes['receive_prize_mobile'];
    }
    
    return null;
  }

  /**
 * 自动填充修复部分prize 字段
 * @return [type] [description]
 */
  protected function paddingPrizes($type, $prizes) {
    if (empty($prizes)) {
        return $prizes;
    }

    $old_prizes = $prizes;
    $prizes = htmlspecialchars_decode($prizes);
    $prizes = json_decode($prizes, true);
    if (is_null($prizes)) {
        Log::record("[".date('Y-m-d H:i:s')."]Exception activity prizes[action:preview activity]:{$activity_info['prizes']}");
    } else {
        if (in_array($type, array(
          ActivityConst::TYPE_DOUBLE_ELEVEN,
          ActivityConst::TYPE_CHRISTMASGIFT,
          ActivityConst::TYPE_INGOTS,
          ActivityConst::TYPE_FIRE,
          ActivityConst::TYPE_TREE,
        ))) {
            $discount_level = $prizes['discount_level'];
            foreach ($discount_level as $key => $item) {
                $original_price = isset($item['original_price']) ? $item['original_price'] : $prizes['original_price'];
                $prizes['discount_level'][$key]['discount_price'] = sprintf("%.1f",substr(sprintf("%.2f", $original_price/100*$item['discount']/10), 0, -1));
                if (isset($item['used_stock'])) {
                    $prizes['discount_level'][$key]['true_left_stock'] = $item['left_stock'] - $item['used_stock'];
                    $prizes['discount_level'][$key]['percent'] = $item['left_stock'] == 0 ? 0 : (($item['left_stock'] - $item['used_stock'])/$item['left_stock']*100);
                } else {
                    $prizes['discount_level'][$key]['true_left_stock'] = $item['left_stock'];
                    $prizes['discount_level'][$key]['percent'] = $item['left_stock'] == 0 ? 0 : 100;
                }
            }    

            $prizes = json_encode($prizes);

            return $prizes;
        }

        //为电话号码为座机号的转为字符串类型，解决json解析的问题
        if (is_array($prizes)) {
          if (isset($prizes['mobile']) && is_numeric($prizes['mobile'])) {
            $first_word = substr( $prizes['mobile'], 0, 1 );
            if ($first_word < 1 ) {
              $prizes['mobile'] = $prizes['mobile'].' ';
              $prizes = json_encode($prizes);

              return $prizes;
            }
          }
          if (isset($prizes['receive_prize_mobile']) && is_numeric($prizes['receive_prize_mobile'])) {
            $first_word = substr( $prizes['receive_prize_mobile'], 0, 1 );
            if ($first_word < 1 ) {
              $prizes['receive_prize_mobile'] = $prizes['receive_prize_mobile'].' ';
              $prizes = json_encode($prizes);

              return $prizes;
            }
          }
        }
    }
    return $old_prizes;
  }

  //判断是否为我的活动
  protected function isMyActivity($activity_id) {
      $user_id = USER_ID;
      $activity_info = M('Activities')->where("id={$activity_id}")->field('id, user_id, type, end_time')->find();
      if (is_null($activity_info) || $activity_info['user_id'] != $user_id) $this->failReturn(C('ACTIVITY_IS_NOT_EXIST'));
    
      return $activity_info;
  }
}

