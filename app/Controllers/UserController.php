<?php

namespace App\Controllers;

use App\Models\User;
use ManaPHP\Rest\Controller;
use App\Services\WechatService;

/**
 * Class UserController
 * @package App\Controllers
 * @property-read WechatService $wechatService
 */
class UserController extends Controller
{
    public function loginAction()
    {
        $code = input('code', ['string']);
        $wechat = $this->wechatService->login($code);
        if (isset($wechat['errcode']) && $wechat['errcode'] !==0) {
            return $wechat['errmsg'];
        }
        $this->logger->info($wechat);
        if (!is_array($wechat) || !isset($wechat['openid'])) {
            return 'openid获取失败';
        }
        $user = User::first(['openId' => $wechat['openid']]);
        if (is_null($user)) {
            User::rCreate(['openid' => $wechat['openid']]);
            return ['code' => 1, 'message' => '用户未授权信息同步'];
        }
        $user->session_key = $wechat['session_key'];
        $user->save();
        $ttl = seconds('7d');
        $info = [
            'user_id' => $user->user_id,
            'openid' => $user->openid,
            'unionid' => $user->unionid,
            'session_key' => $user->session_key,
            'avatar' => $user->avatarUrl,
            'user_name' => $user->nickName
        ];

        $token = jwt_encode($info, $ttl, 'user');

        return ['openid' => $user->openid, 'token' => $token, 'ttl' => $ttl - seconds('1d')];
    }

    public function authAction()
    {
        $user_id = $this->identity->getId();
        if ($user_id < 0) {
            return '未登录';
        }

        $avatarUrl = input('avatarUrl', ['string']);
        $city = input('city', ['string']);
        $country = input('country', ['string']);
        $gender = input('gender', ['string']);
        $nickName = input('nickName', ['string']);
        $province = input('province', ['string']);

        $user = User::get($user_id);
        $user->openid = $this->identity->getClaim('openid');
        $user->unionid = $this->identity->getClaim('unionid');
        $user->avatarUrl = $avatarUrl;
        $user->city = $city;
        $user->country = $country;
        $user->gender = $gender;
        $user->nickName = $nickName;
        $user->province = $province;
        $user->login_time = time();
        $user->login_ip = $this->request->getClientIp();
        return $user->save();
    }

    public function balanceAction()
    {
        $user_id = $this->identity->getId();
        if ($user_id < 0) {
            return '未登录';
        }
        return User::value(['user_id' => $user_id], 'balance');
    }
}
