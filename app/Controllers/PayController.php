<?php

namespace App\Controllers;

use App\Models\PayLog;
use App\Models\PayLogType;
use App\Models\User;
use ManaPHP\Rest\Controller;

class PayController extends Controller
{
    public function photoAction()
    {
        $amount = input('amount', ['int', 'min' => 0.01]);
        $pay_json = input('pay_json', ['string', 'default' => '']);
        $user_id = $this->identity->getId();
        if ($user_id < 0) {
            return '用户未授权登录';
        }

        try {
            $throwable = null;
            $this->db->begin();

            $log_type = PayLogType::first(['code' => 'photo.take.pay']);
            $user = User::get($user_id);
            $log = new PayLog();

            $log->type_id = $log_type->id;
            $log->type_name = $log_type->name;
            $log->user_id = $user_id;
            $log->balance = $user->balance - $amount;
            $log->amount = $amount;
            $log->pay_info = $pay_json;
            $log->create();

            $user->balance = $user->balance - $amount;
            $user->update();
            return ['code' => 0, 'message' => '支付成功', 'balance' => $user->balance];

        } catch (\Throwable $throwable) {
            $this->logger->error($throwable);
            return $throwable;
        } finally {
            if ($throwable) {
                $this->db->rollback();
            } else {
                $this->db->commit();
            }
        }
    }
}
