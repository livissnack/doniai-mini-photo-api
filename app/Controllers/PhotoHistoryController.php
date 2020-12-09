<?php

namespace App\Controllers;

use App\Models\PhotoHistory;
use App\Models\PhotoSpec;
use ManaPHP\Rest\Controller;

class PhotoHistoryController extends Controller
{
    public function indexAction()
    {
        $user_id = $this->identity->getId();
        if ($user_id < 0) {
            return '用户未授权登录';
        }
       $data = PhotoHistory::select(['ph_id', 'image_url', 'size', 'remark', 'created_time', 'spec_id'])
           ->where(['user_id' => $user_id])
           ->orderBy(['created_time' => SORT_DESC])
           ->paginate();
        foreach ($data->items as $k => &$v) {
            if (!is_null($v['created_time'])) {
                $v['created_time'] = date('Y-m-d H:i:s', $v['created_time']);
            }
            if (!is_null($v['spec_id'])) {
                $v['spec'] = PhotoSpec::value(['spec_id' => $v['spec_id']], 'spec_name');
            }
        }
        return $data;
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
        return PhotoHistory::first(['user_id' => $user_id, 'ph_id' => $ph_id], ['ph_id', 'image_url', 'print_image_url', 'photo_key', 'size', 'remark', 'created_time']);
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
