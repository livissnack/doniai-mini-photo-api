<?php

namespace App\Services;

use Throwable;
use ManaPHP\Service;
use ManaPHP\Exception\InvalidValueException;
use ManaPHP\Exception\MissingFieldException;

/**
 * Class BaiduService
 * @package App\Services
 */
class BaiduService extends Service
{
    protected $_base_url;

    protected $_access_key;

    protected $_secret_key;

    public function ocr_plant($img)
    {
        try {
            $params = [
                'access_token' => $this->_token(),
            ];
            $request_data = ['image' => base64_encode($img)];
            $request_url = $this->_base_url . '/image-classify/v1/plant?' . http_build_query($params);
            $response = rest_post($request_url, $request_data, ['Content-Type' => 'application/x-www-form-urlencoded'])->body;
            if (!isset($response['error'])) {
                return $response;
            } else {
                throw new \Exception('OCR植物识别失败');
            }
        } catch (\Throwable $throwable) {
            return $throwable->getMessage();
        }
    }


    private function _token()
    {
        try {
            $redis_key = 'baidu:access_token';

            $access_token = $this->redisDb->get($redis_key);
            if ($access_token !== false) {
                return $access_token;
            }
            $params = [
                'grant_type' => 'client_credentials',
                'client_id' => $this->_access_key,
                'client_secret' => $this->_secret_key,
            ];
            $request_url = 'https://aip.baidubce.com/oauth/2.0/token?'.http_build_query($params);
            $response = rest_get($request_url, [])->body;
            if (!isset($response['error'])) {
                $this->redisDb->set($redis_key, $response['access_token'], seconds('30d'));
                return $response;
            } else {
                throw new \Exception('百度认证token获取失败');
            }
        } catch (\Throwable $throwable) {
            return $throwable->getMessage();
        }
    }
}
