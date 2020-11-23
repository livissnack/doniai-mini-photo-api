<?php

namespace App\Controllers;

use App\Models\PhotoSpec;
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
        return 'hello world';
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
