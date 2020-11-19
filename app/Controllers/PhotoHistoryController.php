<?php

namespace App\Controllers;

use App\Models\PhotoHistory;
use ManaPHP\Rest\Controller;

class PhotoHistoryController extends Controller
{
    public function indexAction()
    {
        $user_id = $this->identity->getId();
        if ($user_id < 0) {
            return '用户未授权登录';
        }
       return PhotoHistory::select(['ph_id', 'image_url', 'size', 'remark', 'created_time'])
           ->where(['user_id' => $user_id])
           ->paginate();
    }

    public function detailAction()
    {
        $user_id = $this->identity->getId();
        if ($user_id < 0) {
            return '用户未授权登录';
        }
        $ph_id = input('ph_id', ['int', 'default' => 0]);
        if ($ph_id <= 0) {
            return '请求参数错误';
        }
        return PhotoHistory::first(['user_id' => $user_id, 'ph_id' => $ph_id], ['ph_id', 'image_url', 'print_image_url', 'size', 'remark', 'created_time']);
    }

    public function deleteAction()
    {
        $user_id = $this->identity->getId();
        if ($user_id < 0) {
            return '用户未授权登录';
        }
        $ph_id = input('ph_id', ['int', 'default' => 0]);
        if ($ph_id <= 0) {
            return '请求参数错误';
        }

        return PhotoHistory::rDelete();
    }
}
