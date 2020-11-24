<?php

namespace App\Controllers;

use App\Models\PhotoSpec;
use App\Models\User;
use App\Services\AliMarketService;
use App\Services\AliyunOssService;
use App\Services\BaiduService;
use App\Services\PhotoService;
use App\Services\WechatService;
use ManaPHP\Helper\LocalFS;
use ManaPHP\Rest\Controller;
use ManaPHP\Security\Random;

/**
 * Class TestController
 * @package App\Controllers
 * @property-read WechatService $wechatService
 * @property-read AliMarketService $aliMarketService
 * @property-read BaiduService $baiduService
 * @property-read PhotoService $photoService
 */
class TestController extends Controller
{
    public function getAcl()
    {
        return ['*' => '*'];
    }

    public function indexAction()
    {
        $appid = param_get('wechat_mini_app_id');

        $str = '恭喜你兑换成功<a data-miniprogram-appid=\$appid\' data-miniprogram-path=\'pages/index/index\'>智能证照欢迎你</a>';
        return $str;

        $user = User::get(2);
        $temp_data = [
            'touser' => $user->openid,
            'template_id' => 'XFNW9Kc_6dqXPUgTrHQQ85doNL2poukrz2pzmCWrD8o',
            'page' => 'index',
            'miniprogram_state' => 'trial',
            'lang' => 'zh_CN',
            'data' => [
                'character_string1' => ['value' => 'adadada'],
                'thing2' => ['value' => 'adadas'],
                'number3' => ['value' => 1],
                'time4' => ['value' => date('Y-m-d H:i:s', time())],
                'thing5' => ['value' => ['未支付', '已支付', '已退款', '运输中', '已完成'][4]],
            ],
        ];
        $res = $this->wechatService->send_subscribe_msg($temp_data);
        if (is_string($res)) {
            $send_data = [
                'touser' => $user->openid,
                'msgtype' => 'text',
                'text' => [
                    'content' => '文本内容...<a href="http://www.qq.com" data-miniprogram-appid="wx12e6720347a6907f" data-miniprogram-path="pages/index/index">点击跳小程序</a>'
                ],
            ];
            $this->wechatService->send_custom_msg($send_data);
        }
    }

    public function save_spec($data, $spec_id)
    {
        $spec = PhotoSpec::first(['spec_id' => $spec_id]);
        $spec->spec_name = $data['spec_name'];
        $spec->background_color = $data['background_color'];
        $spec->file_size_max = $data['file_size_max'];
        $spec->file_size_min = $data['file_size_min'];
        $spec->height_mm = $data['height_mm'];
        $spec->height_px = $data['height_px'];
        $spec->is_print = $data['is_print'];
        $spec->ppi = $data['ppi'];
        $spec->width_mm = $data['width_mm'];
        $spec->width_px = $data['width_px'];
        return $spec->update();
    }

    public function update_price() {
        PhotoSpec::updateAll(['price' => 4.99], ['enabled' => 1]);
    }
}
