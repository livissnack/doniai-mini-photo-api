<?php

namespace App\Controllers;

use ManaPHP\Rest\Controller;

class SigninController extends Controller
{
    public function weekdaysAction()
    {
        $time = time();
        $week = date('w', $time);
        $name = date('Y', $time).'年'.date('m', $time).'月';
        $date = [];
        for ($i = 1; $i <= 7; $i++) {
            $date[$i] = date('m-d', strtotime('+' . $i - $week . ' days', $time));
        }
        return ['name' => $name, 'list' => array_values($date)];
    }

    public function doAction()
    {
        try {
            $success = false;
            $this->db->begin();
        } catch (\Throwable $throwable) {
            $this->logger->error($throwable);
            return $throwable;
        } finally {
            if ($success) {
                $this->db->commit();
                return ['code' => 0, '签到成功'];
            } else {
                $this->db->rollback();
                return '签到失败';
            }
        }
    }
}
