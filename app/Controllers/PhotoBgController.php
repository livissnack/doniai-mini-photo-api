<?php

namespace App\Controllers;

use App\Models\PhotoBg;
use ManaPHP\Rest\Controller;

class PhotoBgController extends Controller
{
    private $_redis_bg_key = 'photo:map:bg';

    public function indexAction()
    {
        $bgs = $this->redisDb->get($this->_redis_bg_key);
        if (!is_bool($bgs)) {
            return $bgs;
        }
        $bgs = PhotoBg::all(['enable' => 1], null, ['bg_id', 'name', 'value']);
        $this->redisDb->set($this->_redis_bg_key, $bgs, seconds('7d'));

        return $bgs;
    }
}
