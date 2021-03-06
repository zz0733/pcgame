<?php
require('../../include/config.inc.php');
require('../../include/function.php');
require('../../include/common.php');
require('../../include/configApi.php');
require('./user.game_api.php');

/**
 *http://api.demo.com/api/pc_egg/get_reward_by_api.php?merid=7778&user_name=allen&keycode=szdfgsd
 */

$user_name = htmlspecialchars(trim($_GET['user_name']));
$merid     = htmlspecialchars(trim($_GET['merid']));
$keycode   = htmlspecialchars(trim($_GET['keycode']));
$game_id   = htmlspecialchars(trim($_GET['gid']));

$key       = '87b5b787044d2ec2';	//定义由PC蛋蛋提供的KEY 需保密；

$state = 1;

//判断参数是否为空
if ($user_name == '' || $merid == '' || $keycode == '') {
    $state = 0;
    $content = '所有参数均不能为空!';
}

//验证签名
if ($keycode != md5($merid . $key)) {
    $state = 0;
    $content = "验证码不对!";
}

$sql = "SELECT uid FROM 91yxq_users.users WHERE uid = " . $merid;
$result = $mysqli->query($sql)->fetch_assoc();
if (!$result) {
    $content = "用户不存在";
    $state = 0;
}
switch ($game_id) {
    case 1:
        $server_id_arr = [19, 20, 21, 22];break;   //魔神变
    case 2:
        $server_id_arr = [8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31, 32, 33, 34, 35];break;   //斗破沙城
    case 3:
        $server_id_arr = [8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31, 32, 33, 34, 35];break;   //热血虎卫
}

$gameUserModel = new GameUser();
$max = 0;
$_nickname = '';
foreach ($server_id_arr as $val) {
    $result = $gameUserModel->main($game_id, $val, $user_name);
    switch ($game_id) {
        case 1:                           //魔神变
            $status = $result['code'] === 0 ? true : false;
            $level = $result['data']['level'];
            $nickname = $result['data']['nickname'];
            break;
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
        default:
            $status = $result['code'] === 0 ? true : false;
            $level = $result['data']['level'];
            $nickname = $result['data']['nickname'];
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
//    $content = iconv("gb2312","utf-8",$content);
    $xml = '<?xml version="1.0" encoding="utf-8" ?>';
    $xml .= '<Result>';
    $xml .= '<ErrMsg>' . $content . '</ErrMsg>';
    $xml .= '<Status>-1</Status>';
    $xml .= '</Result>';
} else {
    $xml = '<?xml version="1.0" encoding="utf-8" ?>';
    $xml .= '<Result>';
    $xml .= '<GameName>' . $_nickname . '</GameName>';
    $xml .= '<PlayLevel>' . $max . '</PlayLevel>';
    $xml .= '<TodayLevel>1</TodayLevel>';
    $xml .= '<Status>1</Status>';
    $xml .= '</Result>';
}
echo $xml;
