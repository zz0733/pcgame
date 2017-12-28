<?php
require('../../include/config.inc.php');
require('../../include/function.php');
require('../../include/common.php');
require('../../include/configApi.php');
require('../pc_egg/user.game_api.php');

/**
 *http://api.91yxq.com/api/shitoucun/get_reward_by_api.php?guid=54316&gid=3
 */

$guid     = htmlspecialchars(trim($_GET['guid']));
$game_id   = htmlspecialchars(trim($_GET['gid']));
$state = 1;

//判断参数是否为空
if ($guid == '' || $game_id == '') {
    $state = 0;
    $content = '所有参数均不能为空!';
}

$sql = "SELECT uid, user_name FROM 91yxq_users.users WHERE uid = " . $guid;
$res = $mysqli->query($sql)->fetch_assoc();
if (!$res) {
    $content = "用户不存在";
    $state = 0;
}
switch ($game_id) {
    case 3:
        $server_id_arr = [46, 50, 51, 52, 53, 54, 55, 56, 57, 58, 59, 60, 61, 62, 63];break;   //热血虎卫
}

$mem = new Memcache();
$link = $mem->connect('127.0.0.1', 11211);
$data = $mem->get($guid);

if ($data !== false) {
    $json = unserialize($data);
} else {
    $gameUserModel = new GameUser();
    $max = 0;
    $_nickname = '';
    foreach ($server_id_arr as $val) {
        $result = $gameUserModel->main($game_id, $val, $res['user_name']);
        switch ($game_id) {
            case 2:                           //斗破沙城
                $status = $result['type'] === 1 ? true : false;
                $level = $result['value'];
                $nickname = $result['message'];
                break;
            case 3:                           //热血虎卫
                $status = $result['code'] === 1 ? true : false;
                $level = $result['data']['level'];
                $nickname = $result['data']['name'];
                break;
        }
        if ($status) {
            if ($max < $level) {
                $max = $level;
                $_nickname = $nickname;
            }
        }
    }

    if ($max == 0) {
        $content = "获取游戏信息出错!";
        $state = 0;
    }

    if ($state == 0) {
        $arr               = [];
    } else {
    //GameStatus       是                     0 | 1 0代表此用户不正常,1为相反
    //GameUid          是                     [游戏平台用户ID]
    //GameCid          是                     [石头村用户ID]
    //GameLevel        是                     [角色等级]
    //GameRole         是                     [角色名称]
    //GamePayment      是                     [用户本期合作充值金额](元)
    //GameName         否                     [游戏名称]
    //GameServer       否                     [游戏服务器名称]
        $sql = "SELECT sum(paid_amount) AS total FROM 91yxq_recharge.pay_orders WHERE `user` = '" . $res['user_name'] . "' AND succ = 1";
        $_result = $mysqli->query($sql)->fetch_assoc();
        if (!$_result['total']) {
            $_result['total'] = 0;
        }

        $arr               = [];
        $arr['GameUid']     = $res['uid'];             //用户ID
        $arr['GameRole']   = $_nickname;               //用户角色
        $arr['GameLevel']  = $max;                     //用户等级
        $arr['GamePayment']    = $_result['total'];    //充值总额
        $arr['GameStatus']     = 1;                    //查询信息是否正常（1：正常；0异常）
    }
    $json = json_encode($arr);
    $mem->set($guid, serialize($json), MEMCACHE_COMPRESSED, 600);
}
echo $json;