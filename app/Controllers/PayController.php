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
        $amount = input('amount', ['float', 'min' => 0.01]);
        $pay_json = input('pay_json', ['string', 'default' => '']);
        $user_id = $this->identity->getId();
        if ($user_id < 0) {
            return '用户未授权登录';
        }

        try {
            $success = false;
            $data = false;
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

            $success = true;
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