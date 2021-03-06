<?php

namespace App\Controllers;

use App\Services\BaiduService;
use ManaPHP\Helper\LocalFS;
use ManaPHP\Rest\Controller;
use ManaPHP\Security\Random;
use App\Models\PhotoHistory;
use App\Services\WechatService;
use App\Services\AliMarketService;
use App\Services\AliyunOssService;

/**
 * Class AiController
 * @package App\Controllers
 * @property-read WechatService $wechatService
 * @property-read BaiduService $baiduService
 * @property-read AliMarketService $aliMarketService
 */
class AiController extends Controller
{
    public function photoToTextAction()
    {
        $file = $this->request->getFile();
        $spec_id = input('spec_id', ['int', 'default' => 2]);
        $bk = input('bk', ['in' => 'red,blue,white', 'default' => 'blue']);
        if (!is_file($file->getTempName())) {
            return '上传文件异常';
        }
        $file_data = file_get_contents($file->getTempName());
        $base_img = chunk_split(base64_encode($file_data));
        $make_data = $this->aliMarketService->photo($base_img, $spec_id, $bk, $file->getExtension());
        if (!isset($make_data['result'])) {
            return $make_data;
        }

        $target = http_download($make_data['result']);

        $ext_name = pathinfo($target, PATHINFO_EXTENSION);

        $bucket_name = param_get('ali_oss_bucket_name');
        $content_type = 'image/' . $ext_name;
        $filename = md5((new Random())->getUuid()) . '.' . $ext_name;
        AliyunOssService::publicUpload($bucket_name, $filename, $target, ['ContentType' => $content_type]);
        unlink($target);
        $photo_url = AliyunOssService::getPublicObjectURL($bucket_name, $filename);
        $photo_history = new PhotoHistory();
        $photo_history->image_url = $photo_url;
        $photo_history->size = $make_data['size'] ?? '';
        $photo_history->user_id = $this->identity->getId();
        $photo_history->spec_id = $spec_id;
        $photo_history->image_url = str_replace('http://', 'https://', $photo_url);
        $photo_history->photo_key = $make_data['photo_key'];
        $photo_history->save();
        return ['code' => 0, 'message' => '制作成功', 'data' => $photo_history->image_url];
    }

    public function ocrPrintedTextAction()
    {
        $file = $this->request->getFile();

        if ($file->getSize() > 10485760) {
            return '文件太大了，识别不了';
        }

        $bucket_name = param_get('ali_oss_bucket_name');
        $this->checkDir($bucket_name);
        $target = path("@tmp/uploads/{$bucket_name}/{$file->getName()}");
        $file->moveTo($target, 'jpg,jpeg,png,gif', true);
        $content_type = 'image/' . $file->getExtension();
        $filename = md5((new Random())->getUuid()) . '.' . $file->getExtension();
        AliyunOssService::publicUpload($bucket_name, $filename, $target, ['ContentType' => $content_type]);
        unlink($target);
        $url = AliyunOssService::getPublicObjectURL($bucket_name, $filename);

        if (is_string($url)) {
            $ocr_res = $this->wechatService->ocr_printed_text($url);
            $str = '';
            foreach ($ocr_res as $value) {
                $str .= $value['text'].';';
            }
            return ['code' => 0, 'message' => '识别成功', 'data' => $str];
        }
        return '识别失败';
    }

    public function ocrPlantAction()
    {
        $file = $this->request->getFile();
        if (!is_file($file->getTempName())) {
            return '上传文件异常';
        }
        $file_data = file_get_contents($file->getTempName());
        $base_img = chunk_split(base64_encode($file_data));

        return $this->baiduService->ocr_plant($base_img);
    }

    public function translateAction()
    {
        $q = input('q', ['string']);
        $from = input('from', ['string', 'default' => 'zh-CN']);
        $to = input('to', ['string', 'default' => 'en_US']);
        return $this->wechatService->trans($q, $from, $to);
    }

    /**
     * 检查临时存放目录是否存在
     * @param $bucket_name
     */
    private function checkDir($bucket_name)
    {
        $tmpPath = path('@tmp');
        $uploadPath = path('@tmp/uploads');
        $bucketPath = path('@tmp/uploads/' . $bucket_name);

        if (!LocalFS::dirExists($tmpPath)) {
            LocalFS::dirCreate($tmpPath);
        }

        if (!LocalFS::dirExists($uploadPath)) {
            LocalFS::dirCreate($uploadPath);
        }

        if (!LocalFS::dirExists($bucketPath)) {
            LocalFS::dirCreate($bucketPath);
        }
    }
}
