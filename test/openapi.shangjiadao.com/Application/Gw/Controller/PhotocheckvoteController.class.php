<?php

namespace Gw\Controller;

use Think\Log;
use Gw\Controller\ActivityController;
use Gw\Controller\CommonController;
use Gw\Constants\ActivityConst;
use Gw\Constants\JoinerConst;
use Gw\Constants\StatisticConst;
use Gw\Logic\StatisticLogic;
use Gw\Logic\Ip2Region;
use Gw\Logic\RedisCache;
use Gw\Util\IDUtils;

// ok openapi 引入
use OK\PhpEnhance\DomainObject\FailureResultDO;
use OK\PhpEnhance\DomainObject\ServiceResultDO;
use OK\PhpEnhance\DomainObject\SuccessResultDO;

class PhotocheckvoteController extends ActivityController
{

    /**
     * @Notes:   商家岛新的投票验证方法
     * @param    [type]     $activity_id [活动id]
     * @param    [type]     $joiner_id   [参赛者id]
     * @param    [type]     $open_id     [用户openid]
     * @param    [type]     $code        [验证码]
     * @param    [type]     $avator      [微信头像]
     * @param    [type]     $nickname    [微信昵称]
     * @return   [type]                  [description]
     */
    public function newvote($activity_id, $joiner_id, $open_id, $code, $avator, $nickname)
    {
        // 获取投票信息
        $open_id = trim($open_id);
        $code = trim($code);

        //Log::record('[gw-vote-log]'.$joiner_id);

        // 获取活动信息
        $activity_info = self::getActivityInfoByJoinerId($joiner_id);
        if ($activity_info instanceof FailureResultDO) {
            return $activity_info;
        }

        $type = intval($activity_info['type']);
        $activity_id = $activity_info['id'];

        if (!in_array($type, [
            ActivityConst::TYPE_PHOTO_VOTE_CHECK,
        ])) {
            $err = C('ACTIVITY_IS_NOT_EXIST');
            return new FailureResultDO($err[0], $err[1]);
        }

        //审核状态不通过，不能投票
        $verify_cond = array(
            'id' => $joiner_id,
            'activity_id' => $activity_id,
        );

        $status = M('PhotoVoteJoinerVerify')->where($verify_cond)->getField('status');

        if (!$status) {
            $err = C('JOINER_MEMBER_IS_NOT_VERIFY');
            return new FailureResultDO($err[0], $err[1]);
        }

        $vote_time = time();
        if ($vote_time < $activity_info['start_time']) {
            $err = C('ACTIVITY_HAS_NOT_STARTED');
            return new FailureResultDO($err[0], $err[1]);
        }
        if ($vote_time > $activity_info['end_time']) {
            $err = C('ACTIVITY_HAS_BEEN_COMPLETED');
            return new FailureResultDO($err[0], $err[1]);
        }

        /*************根据设定好的阀值监控投票活动****************/
        //超过设定阀值开始监测
        $Redis = RedisCache::getInstance(C('REDIS_HOST'), C('REDIS_PORT'), C('REDIS_AUTH'));
        $monitor_votelimit_key = 'sjd_monitor_activity_vote_limit_value';  //后台设定的监测阀值
        //获取投票的阀值
        $monitor_vote_limit_value = $Redis->get($monitor_votelimit_key);
        if(!$monitor_vote_limit_value){
            $Redis->set($monitor_votelimit_key, 100);  //默认阀值100,可通过管理后台设置
            $monitor_vote_limit_value = $Redis->get($monitor_votelimit_key);
        }

        $activity_votelimit_for_day = 'sjd_monitor_activity_vote_for_day_' . date('md'); //监控记录的集合
        $member_key = $activity_id .'_'.$joiner_id . '_' .date('Y-m-d'). '_'.date('H');    //集合的成员名称
        $monitor_votelimit_consult_key = 'sjd_monitor_activity_vote_consult_for_hour_' . date('mdH');    //参考值的名称
        $member_consult_key = $activity_id . '_' . $joiner_id;

        //判断参考值key是否存在,不存在创建，时长为1小时
        if (!($Redis->exists($monitor_votelimit_consult_key))) {
            $Redis->zAdd($monitor_votelimit_consult_key, 0, $member_consult_key); //集合不存在则创建
            $Redis->expire($monitor_votelimit_consult_key, 3600);
        }

        //参考值自增
        $Redis->zIncrBy($monitor_votelimit_consult_key, 1, $member_consult_key);

        //判断活动key是否存在,不存在创建，时长为5天
        if (!($Redis->exists($activity_votelimit_for_day))) {
            $Redis->zAdd($activity_votelimit_for_day, 0, $member_key); //集合不存在则创建
            $Redis->expire($activity_votelimit_for_day, 432000);
        }

        //检查参考值是否超过阀值，超过则开始记录参赛者的投票行为
        $joiner_score = $Redis->zScore($monitor_votelimit_consult_key, $member_consult_key);  //获取成员参考值
        if ($joiner_score > $monitor_vote_limit_value) {  //如果存在该成员且大于阀值
            $Redis->zIncrBy($activity_votelimit_for_day, 1, $member_key);
        }

        /***********以下是活动限制规则验证操作**************/
        $Voters = M('Voters');
        $PhotoVoters = M('PhotoVoters');
        $today = date('ymd');

        // 检测是否已经用完投票机会
        try {
            $prizes = json_decode(htmlspecialchars_decode($activity_info['prizes']), true);
        } catch (Exception $e) {
            $prizes = array();
        }

        if (!isset($prizes['count']) || $prizes['count'] <= 0) {
            $err = C('VOTE_FAILED');
            return new FailureResultDO($err[0], $err[1]);
        }

        // 客户端ip处理
        $ip = get_client_ip(1, true);

        //是否在投票区域内
        if ($prizes['area_verify']) {
            $Ip2Region = new Ip2Region();
            $client_area_info = $Ip2Region->memorySearch($ip);

            if (($prizes['area_province'] != $client_area_info['area_province'])) {
                $err = C('AREA_IS_NOT_ALLOW_VOTE');
                return new FailureResultDO($err[0], $err[1]);
            }

            if (!empty($prizes['area_city'])) {
                $check_result = explode($client_area_info['area_city'], $prizes['area_city']);
                if (count($check_result) <= 1) {
                    $err = C('AREA_IS_NOT_ALLOW_VOTE');
                    return new FailureResultDO($err[0], $err[1]);
                }
            }
        }

        $voter = $PhotoVoters->where([
            'activity_id' => $activity_id,
            'open_id' => $open_id,
            'cdate' => intval($today),
        ])->field('id, vote_count')->find();

        if ($voter && !empty($voter) && $voter['vote_count'] >= $prizes['count']) {
            $err = C('HAS_ALREADY_REACH_VOTE_LIMIT');
            return new FailureResultDO($err[0], $err[1]);
        }

        // 开启验证码
        $verify = new \Think\Verify();
        if (((isset($prizes['open_verify']) && $prizes['open_verify'] == '1') || !isset($prizes['open_verify'])) &&
            !$verify->check($code, 'photovote_verify_' . $joiner_id)) {
            $err = C('VERIFY_CODE_IS_REQUIRE');
            return new FailureResultDO($err[0], $err[1]);
        }

        //每小时投票数限制
        $activity_set_id = 'sjd_activity_photovote_limit_for_hour_' . $activity_id . '_' . date('mdH');
        $activity_vote_joiner = $joiner_id;
        if ($prizes['hour_vote_verify']) {
            //判断活动key是否存在,不存在创建，时长为5天
            if (!($Redis->exists($activity_set_id))) {
                $Redis->zAdd($activity_set_id, 0, $activity_vote_joiner);
                $Redis->expire($activity_set_id, 432000);
            }

            //检查参赛者是否存在
            $joiner_score = $Redis->zScore($activity_set_id, $activity_vote_joiner);
            if (!$joiner_score) {
                $Redis->zAdd($activity_set_id, 0, $activity_vote_joiner);
            } elseif ($joiner_score >= $prizes['hour_vote_limit']) {
                //1小时内超过限定的投票数给出提示
                $err = C('HAS_ALREADY_REACH_HOUR_VOTE_LIMIT');
                return new FailureResultDO($err[0], $err[1]);
            }
        }

        /***********以下是业务更新和日志记录操作**************/

        $conditions = array(
            'id' => $joiner_id,
            'status' => JoinerConst::STATUS_VALID,
        );

        if (in_array($type, [
            ActivityConst::TYPE_PHOTO_VOTE_CHECK,
        ])) {
            $nickname = trim($nickname);
            $avator = trim($avator);
            $wechat_voter = array(
                'joiner_id' => $joiner_id,
                'activity_id' => $activity_id,
                'ip' => $ip,
                'vote_time' => time(),
                'nickname' => $nickname,
                'avator' => $avator,
                'vote_count' => 1,
            );

            // 新增失败
            $res = M('WechatVoters')->add($wechat_voter);
            if ($res === false) {
                $err = C('VOTE_FAILED');
                return new FailureResultDO($err[0], $err[1]);
            }
        }

        // 获取参加者信息
        $Joiners = M('Joiners');
        $vote_count = $Joiners
            ->where($conditions)
            ->getField('vote_count');

        $vote_result = $Joiners->save(array(
            'id' => $joiner_id,
            'vote_count' => $vote_count + 1,
            'update_time' => time(),
        ));

        if ($vote_result === false) {
            $err = C('VOTE_FAILED');
            return new FailureResultDO($err[0], $err[1]);
        }

        if (in_array($type, [
            ActivityConst::TYPE_PHOTO_VOTE_CHECK,
        ])) {
            $voter_data = array(
                'activity_id' => $activity_id,
                'open_id' => $open_id,
                'joiner_id' => $joiner_id,
                'ip' => get_client_ip(1, true),
                'vote_time' => $vote_time,
            );

            $res = $Voters->add($voter_data);
            if ($res === false) {
                $err = C('VOTE_FAILED');
                return new FailureResultDO($err[0], $err[1]);
            }
        }

        // 剩余投票数
        $left_vote = 0;
        if (!empty($voter)) {
            $p_vote_count = $voter['vote_count'] + 1;
            $res = $PhotoVoters->save(array(
                'id' => $voter['id'],
                'vote_count' => $p_vote_count,
            ));
            if ($res === false) {
                $err = C('VOTE_FAILED');
                return new FailureResultDO($err[0], $err[1]);
            }
            $left_vote = $prizes['count'] - $p_vote_count;
        } else {
            $photo_voter_data = array(
                'activity_id' => $activity_id,
                'open_id' => $open_id,
                'cdate' => $today,
                'vote_count' => 1,
            );

            $res = $PhotoVoters->add($photo_voter_data);
            if ($res === false) {
                $err = C('VOTE_FAILED');
                return new FailureResultDO($err[0], $err[1]);
            }

            $left_vote = $prizes['count'] - 1;
        }

        // 更新投票总次数
        StatisticLogic::setActivityStatistics($activity_id, StatisticConst::TYPE_VOTE);

        //增加参赛者的投票数
        $Redis->zIncrBy($activity_set_id, 1, $activity_vote_joiner);

        S("activity_latest_cache_{$activity_id}", null);
        S("activity_ranking_cache_{$activity_id}", null);

        return new SuccessResultDO([
            'left_vote' => $left_vote,
        ]);
    }
}

