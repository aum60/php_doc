<?php
namespace Gw\Logic;

use Gw\Logic\CommonLogic;
use Gw\Constants\JoinerConst;
use Gw\Constants\StatisticConst;

class StatisticLogic extends CommonLogic
{
  /**
   * 获取活动统计数据
   * @param  int $activity_id 活动ID
   * @return array $statistics 活动统计信息
   */
  public static function getActivityStatistics($activity_id) {
    $statistics = M('Statistics')->where('activity_id=' . $activity_id)->field('join_count,share_count,view_count')->select();
    $join_count = array_sum(array_column($statistics, 'join_count'));
    $share_count = array_sum(array_column($statistics, 'share_count'));
    $view_count = array_sum(array_column($statistics, 'view_count'));

    return array(
      'join_count' => (int)$join_count,
      'share_count' => (int)$share_count,
      'view_count' => (int)$view_count,
    );
  }

  /**
   * 获取 抽奖活动 统计数据
   * @param  int $activity_id 活动ID
   * @return array $statistics 活动统计信息
   */
  public static function getLotteryStatistics($activity_id) {
    $statistics = M('Statistics')->where('activity_id=' . $activity_id)->field('join_count,lottery_count,view_count')->select();
    $join_count = array_sum(array_column($statistics, 'join_count'));
    $lottery_count = array_sum(array_column($statistics, 'lottery_count'));
    $view_count = array_sum(array_column($statistics, 'view_count'));

    return array(
      'join_count' => (int)$join_count,
      'lottery_count' => (int)$lottery_count,
      'view_count' => (int)$view_count,
    );
  }

  /**
   * 获取 照片投票活动 统计数据
   * @param  int $activity_id 活动ID
   * @return array $statistics 活动统计信息
   */
  public static function getPhotoVoteStatistics($activity_id) {
    $statistics = M('Statistics')->where('activity_id=' . $activity_id)->field('join_count,vote_count,view_count')->select();
    $join_count = array_sum(array_column($statistics, 'join_count'));
    $vote_count = array_sum(array_column($statistics, 'vote_count'));
    $view_count = array_sum(array_column($statistics, 'view_count'));

    return array(
      'join_count' => (int)$join_count,
      'vote_count' => (int)$vote_count,
      'view_count' => (int)$view_count,
    );
  }

  /**
   * 增加活动浏览量
   * @param  int $activity_id 活动ID
   * @return array $statistics 活动统计信息
   */
  public static function setActivityStatistics($activity_id, $type, $reverse = false, $cdate = null, $by = 1) {
    $join = intval($type) === StatisticConst::TYPE_JOIN ? $by : 0;
    $share = intval($type) === StatisticConst::TYPE_SHARE ? $by : 0;
    $view = intval($type) === StatisticConst::TYPE_VIEW ? $by : 0;
    $lottery = intval($type) === StatisticConst::TYPE_LOTTERY ? $by : 0;
    $vote = intval($type) === StatisticConst::TYPE_VOTE ? $by : 0;
    $pay = intval($type) === StatisticConst::TYPE_PAY ? $by : 0;

    $Statistics = M('Statistics');

    $conditions = array(
      'activity_id' => $activity_id,
      'date'        => is_null($cdate) ? date('Y-m-d', time()) : $cdate,
    );

    $fields = ['id'];
    if ($join > 0) $fields[] = 'join_count';
    if ($share > 0) $fields[] = 'share_count';
    if ($view > 0) $fields[] = 'view_count';
    if ($lottery > 0) $fields[] = 'lottery_count';
    if ($vote > 0) $fields[] = 'vote_count';
    if ($pay > 0) $fields[] = 'pay_count';

    // 获取记录ID
    $statistic = $Statistics->where($conditions)->field(implode(',', $fields))->find();
    if ($reverse && empty($statistic)) {
      return false;
    }

    if (empty($statistic)) {
      $statistic = array(
        'activity_id'   => $activity_id,
        'join_count'    => $join,
        'share_count'   => $share,
        'view_count'    => $view,
        'lottery_count' => $lottery,
        'vote_count'    => $vote,
        'pay_count'    => $pay,
        'date'          => date('Y-m-d', time()),
        'create_time'   => time(),
      );
      $result = $Statistics->add($statistic);
    } else {
      if ($reverse) {
        if ($join > 0) $statistic['join_count'] -= $join;
        if ($share > 0) $statistic['share_count'] -= $share;
        if ($view > 0) $statistic['view_count'] -= $view;
        if ($lottery > 0) $statistic['lottery_count'] -= $lottery;
        if ($vote > 0) $statistic['vote_count'] -= $vote;
        if ($pay > 0) $statistic['pay_count'] -= $pay;
      } else {
        if ($join > 0)  $statistic['join_count'] += $join;
        if ($share > 0) $statistic['share_count'] += $share;
        if ($view > 0) $statistic['view_count'] += $view;
        if ($lottery > 0) $statistic['lottery_count'] += $lottery;
        if ($vote > 0) $statistic['vote_count'] += $vote;
        if ($pay > 0) $statistic['pay_count'] += $pay;
      }

      // $statistic['update_time'] = time();
      $result = $Statistics->save($statistic);
    }

    return $result !== false;
  }
}
