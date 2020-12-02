<?php

namespace App\Controllers;

use App\Models\Order;
use App\Models\PayLog;
use App\Models\PayLogType;
use App\Models\PhotoSpec;
use App\Models\User;
use App\Services\WechatService;
use ManaPHP\Rest\Controller;

/**
 * Class PayController
 * @package App\Controllers
 * @property-read WechatService $wechatService
 */
class PayController extends Controller
{
    public function photoAction()
    {
        $spec_id = input('spec_id', ['int', 'min' => 1]);
        $pay_json = input('pay_json', ['default' => []]);
        $user_id = $this->identity->getId();
        if ($user_id < 0) {
            return '用户未授权登录';
        }

        try {
            $success = false;
            $data = false;
            $this->db->begin();

            $spec = PhotoSpec::first(['spec_id' => $spec_id], ['spec_name', 'price']);

            $log_type = PayLogType::first(['code' => 'photo.take.pay']);
            $user = User::get($user_id);
            if($user->balance < $spec->price) {
                return '积分余额不足';
            }
            $log = new PayLog();

            $log->type_id = $log_type->id;
            $log->type_name = $log_type->name;
            $log->user_id = $user_id;
            $log->balance = $user->balance - $spec->price;
            $log->amount = $spec->price;
            $log->pay_info = json_encode($pay_json);
            $log->create();

            $order = new Order();
            $order->status = Order::STATUS_RECKONED;
            $order->amount = $spec->price;
            $order->total_amount = $spec->price;
            $order->type = Order::TYPE_VIRTUAL;
            $order->is_show = Order::ENABLED_SHOW;
            $order->user_id = $user_id;
            $order->send_name = 'system';
            $order->spec_info = $spec->width_px.'*'.$spec->height_px.'px'.' | '.$spec->width_mm.'*'.$spec->height_mm.'mm';
            $order->delivery_time = time();
            $order->good_name = $spec->spec_name;
            $order->create();

            $user->balance = $user->balance - $spec->price;
            $user->update();

            $success = true;
            $appid = param_get('wechat_mini_app_id');
            $str = "恭喜你支付成功<a data-miniprogram-appid='$appid' data-miniprogram-path='pages/index/index'>智能证照欢迎你</a>";
            $send_data = [
                'touser' => $user->openid,
                'msgtype' => 'text',
                'text' => [
                    'content' => $str
                ],
            ];
            $this->wechatService->send_custom_msg($send_data);
            $data = ['code' => 0, 'message' => '支付成功', 'balance' => $user->balance];

        } catch (\Throwable $throwable) {
            $this->logger->error($throwable);
            return $throwable;
        } finally {
            if ($success) {
                $this->db->commit();
                return $data === false ? '支付失败' : $data;
            } else {
                $this->db->rollback();
            }

        }
    }
}
