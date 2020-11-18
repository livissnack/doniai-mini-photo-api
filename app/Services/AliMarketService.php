<?php

namespace App\Services;

use Throwable;
use ManaPHP\Service;
use ManaPHP\Exception\InvalidValueException;
use ManaPHP\Exception\MissingFieldException;

/**
 * Class AliMarketService
 * @package App\Services
 */
class AliMarketService extends Service
{
    public function express($no = '780098068058', $type = 'zto')
    {
        try {
            if (is_null($no) || is_null($type)) {
                throw new InvalidValueException('物流单号为空');
            }
            $params = [
                'no' => $no,
                'type' => $type
            ];
            $request_url = param_get('ali_wuliu_base_url') . '/kdi?' . http_build_query($params);
            $response = rest_get($request_url, ['Authorization' => 'APPCODE ' . param_get('ali_app_code')], 1.0)->body;
            $this->logger->info($response);
            if ($response['status'] == 0) {
                return $response['result'];
            } else {
                throw new \Exception('物流接口查询失败');
            }
        } catch (Throwable $throwable) {
            return $throwable->getMessage();
        }
    }

    public function photo($image_url, $spec = 2, $bk = 'blue', $ext_name = 'png', $with_photo_key = 1)
    {
        try {
            if (is_null($image_url)) {
                throw new MissingFieldException('图片base64内容为空');
            }

            if (!in_array($bk, ['blue', 'red', 'white'], true)) {
                throw new MissingFieldException('证件照背景值错误');
            }

            if (!in_array($ext_name, ['jpg', 'png'], true)) {
                throw new MissingFieldException('图片格式不支持');
            }

            $body = [
                'type' => $ext_name,
                'photo' => $image_url,
                'spec' => $spec,
                'bk' => $bk,
                'with_photo_key' => $with_photo_key,
                'beauty_degree' => 1.5
            ];
            $request_url = 'https://alidphoto.aisegment.com/idphoto/make';
            $response = rest_post($request_url, $body, ['Authorization' => 'APPCODE ' . param_get('ali_app_code')], 1.0)->body;
            if ($response['status'] == 0 && isset($response['data']['result'])) {
                return $response['data'];
            } else {
                throw new \Exception('证件照制作失败');
            }
        } catch (Throwable $throwable) {
            return $throwable->getMessage();
        }
    }

    public function idphoto($photo_key)
    {
        try {
            if (is_null($photo_key)) {
                throw new MissingFieldException('图片photo_key为空');
            }

            $body = [
                'paper' => 'inch5',
                'photo_key' => $photo_key,

            ];
            $request_url = 'https://idphotox.market.alicloudapi.com/idphoto/arrange';
            $response = rest_post($request_url, $body, ['Authorization' => 'APPCODE ' . param_get('ali_app_code')], 1.0)->body;
            if ($response['status'] == 0 && isset($response['data']['result'])) {
                return $response['data'];
            } else {
                throw new \Exception('证件照制作失败');
            }
        } catch (Throwable $throwable) {
            return $throwable->getMessage();
        }
    }
}
