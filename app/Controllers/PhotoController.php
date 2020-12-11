<?php

namespace App\Controllers;

use App\Models\PhotoHistory;
use App\Services\AliyunOssService;
use App\Services\PhotoService;
use Intervention\Image\ImageManagerStatic;
use ManaPHP\Rest\Controller;
use ManaPHP\Security\Random;

/**
 * Class PhotoController
 * @package App\Controllers
 * @property-read PhotoService $photoService
 */
class PhotoController extends Controller
{
    /**
     * @return PhotoHistory|array|bool|mixed|string|null
     * @throws \JsonException
     * @throws \ManaPHP\Exception\JsonException
     * @throws \ManaPHP\Exception\MisuseException
     * 图片制作
     */
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
            $photo_history->spec_id = $spec_id;
            $res = $photo_history->save();
            $this->redisBroker->lpush('task.sync.list', $res->ph_id);
            return $res;
        }
        return $verify_res;
    }

    /**
     * @return PhotoHistory|string|null
     * @throws \ManaPHP\Exception\MisuseException
     * 获取无水印图片
     */
    public function takeAction()
    {
        $file_name = input('file_name', ['string', 'default' => '']);
        if (is_null($file_name)) {
            return '文件名不能为空';
        }

        $no_water_mark_img = $this->photoService->take($file_name);
        if (!isset($no_water_mark_img['file_name']) || is_null($no_water_mark_img['file_name'])) {
            return '无水印图片获取失败';
        }
        $photo_history = PhotoHistory::first(['photo_key' => $file_name]);
        $photo_history->image_url = $no_water_mark_img['file_name'];
        $photo_history->print_image_url = $no_water_mark_img['file_name_list'];
        return $photo_history->update();
    }

    public function backgroundAction()
    {
        $ph_id = input('ph_id', ['int', 'default' => 0]);
        if ($ph_id < 1) {
            return '该图片未制作成功';
        }
        $file = $this->request->getFile();
        $file_path = $file->getTempName();
        $ext_name = $file->getExtension();
        $bucket_name = param_get('ali_oss_bucket_name');
        $target = path("@tmp/uploads/{$bucket_name}/{$file->getName()}");
        ImageManagerStatic::make($file_path)
            ->fill('#e54d42', 0, 0)
            ->save($target, 100, $ext_name);

        $file->moveTo($target, 'jpg,jpeg,png,gif', true);
        $content_type = 'image/' . $ext_name;
        $filename = md5((new Random())->getUuid()) . '.' . $ext_name;
        AliyunOssService::publicUpload($bucket_name, $filename, $target, ['ContentType' => $content_type]);
        unlink($target);
        $url = AliyunOssService::getPublicObjectURL($bucket_name, $filename);
        if (is_string($url)) {
            $photo_history = PhotoHistory::get($ph_id);
            $photo_history->image_url = $url;
            $photo_history->update();
            return ['code' => 0, 'obj_name' => $filename, 'url' => $url];
        } else {
            return '水印添加失败';
        }
    }
}
