<?php

namespace App\Controllers;

use App\Models\PhotoHistory;
use App\Services\PhotoService;
use ManaPHP\Rest\Controller;

/**
 * Class PhotoController
 * @package App\Controllers
 * @property-read PhotoService $photoService
 */
class PhotoController extends Controller
{
    public function makeAction()
    {
        $file = $this->request->getFile();
        $spec_id = input('spec_id', ['int', 'default' => 1]);
        if (!is_file($file->getTempName())) {
            return '上传文件异常';
        }

        $file_data = file_get_contents($file->getTempName());
        $base_img = chunk_split(base64_encode($file_data));

        $verify_res = $this->photoService->verify($base_img);
        if ($verify_res) {
            $make_res = $this->photoService->make($base_img, $spec_id);

            if (!is_array($make_res)) {
                return $make_res;
            }

            $filename = $make_res['result']['file_name'][0];

            if (PhotoHistory::exists(['photo_key' => $filename])) {
                return PhotoHistory::first(['photo_key' => $filename]);
            }

            $photo_history = new PhotoHistory();
            $photo_history->image_url = $make_res['result']['img_wm_url_list'][0];
            $photo_history->size = json_stringify($make_res['result']['size']);
            $photo_history->print_image_url = $make_res['result']['print_wm_url_list'][0];
            $photo_history->photo_key = $make_res['result']['file_name'][0];
            $photo_history->user_id = $this->identity->getId();
            $photo_history->photo_spec_id = $spec_id;

            return $photo_history->save();
        }
        return $verify_res;
    }

    public function takeAction()
    {
        $file_name = input('file_name', ['string', 'default' => '']);
        if (is_null($file_name)) {
            return '文件名不能为空';
        }

        return $this->photoService->take($file_name);
    }
}
