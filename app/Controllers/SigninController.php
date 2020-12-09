<?php

namespace App\Controllers;

use App\Models\PayLog;
use App\Models\PayLogType;
use App\Models\Sign;
use App\Models\User;
use ManaPHP\Rest\Controller;

class SigninController extends Controller
{
    protected $_one_gift_coin = 1;
    protected $_seven_gift_coin = 3;

    public function weekdaysAction()
    {
        $user_id = $this->identity->getId();
        if ($user_id < 0) {
            return '用户未授权登录';
        }

        $time = time();
        $week = date('w', $time);
        $name = date('Y', $time) . '年' . date('m', $time) . '月';
        $date = [];
        for ($i = 1; $i <= 7; $i++) {
            $date[$i] = date('Ymd', strtotime('+' . $i - $week . ' days', $time));
        }
        $sign_list = array_values($date);
        $sign_list_data = Sign::query()
            ->where(['user_id' => $user_id])
            ->whereIn('sign_date', $sign_list)
            ->select(['sign_id', 'user_id', 'sign_date', 'continue_days', 'created_time'])
            ->all();

        $current_sign = Sign::where(['user_id' => $user_id, 'sign_date' => date('Ymd', $time)])
            ->select(['sign_id', 'user_id', 'sign_date', 'continue_days', 'created_time'])
            ->first();

        $new_sign_data = [];
        foreach ($sign_list as $k => $v) {
            $new_sign_data[$k]['date'] = $v;
            $search_res = array_search($v, array_column($sign_list_data, 'sign_date'));
            $new_sign_data[$k]['is_sign'] = $search_res !== false;
        }
        return ['name' => $name, 'today_sign' => $current_sign, 'sign_data' => $new_sign_data];
    }

    public function doAction()
    {
        $user_id = $this->identity->getId();
        if ($user_id < 0) {
            return '用户未授权登录';
        }

        $today = date("Ymd", time());
        if (Sign::exists(['user_id' => $user_id, 'sign_date' => $today])) {
            return '已经签到了';
        }

        try {
            $success = false;
            $this->db->begin();

            $last_day = date("Ymd", strtotime("-1 day"));
            $sign = Sign::first(['user_id' => $user_id, 'sign_date' => $last_day]);

            if ($sign) {
                $sign_db = new Sign();
                $sign_db->user_id = $user_id;
                $sign_db->sign_date = date('Ymd', time());
                if ($sign->continue_days === 7) {
                    $sign_db->continue_days = 1;
                } else if ($sign->continue_days < 7) {
                    $sign_db->continue_days = $sign->continue_days + 1;
                } else {
                    throw new \Exception('签到异常');
                }
                $sign_db->create();
                $success = true;
            } else {
                $sign_db = new Sign();
                $sign_db->user_id = $user_id;
                $sign_db->sign_date = date('Ymd', time());
                $sign_db->continue_days = 1;
                $sign_db->create();
                $success = true;
            }

            $log_type = PayLogType::first(['code' => 'sign.gift']);
            $user = User::get($user_id);

            if ($sign_db->continue_days === 7) {
                $gift_coin = $this->_seven_gift_coin;
            } else {
                $gift_coin = $this->_one_gift_coin;
            }

            $log = new PayLog();
            $log->type_id = $log_type->id;
            $log->type_name = $log_type->name;
            $log->user_id = $user->user_id;
            $log->balance = $user->balance;
            $log->amount = $gift_coin;
            $log->pay_info = json_encode([]);
            $log->remark = '签到送积分';
            $log->create();


            $user->balance = $user->balance + $gift_coin;
            $user->save();

        } catch (\Throwable $throwable) {
            $this->logger->error($throwable);
            return $throwable;
        } finally {
            if ($success) {
                $this->db->commit();
                return ['code' => 0, 'message' => '签到成功', 'data' => isset($gift_coin) ? $gift_coin : 0];
            } else {
                $this->db->rollback();
                return '签到失败';
            }
        }
    }
}
