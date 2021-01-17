<?php

namespace App\Controllers;

use App\Models\Province;
use ManaPHP\Http\Exception;
use ManaPHP\Rest\Controller;

class CovidController extends Controller
{
    private $_base_url = 'https://ncov.deepeye.tech';

    public function overallAction()
    {
        $response = rest_get($this->_base_url . '/data/data/overall.json')->body;
        return $response;
    }

    public function seriousAction()
    {
        $response = rest_get($this->_base_url . '/data/data/serious.json')->body;
        return $response;
    }

    public function chinaAction()
    {
        $response = rest_get($this->_base_url . '/echarts/map/json/province/china.json')->body;
        return $response;
    }

    public function infoAction()
    {
        $from_city = input('from_city');
        $to_city = input('to_city');
        try {
            $from_city_code = Province::value(['name' => $from_city], 'code');
            $to_city_code = Province::value(['name' => $to_city], 'code');
            $params = [
                'from_city_code' => $from_city_code,
                'to_city_code' => $to_city_code,
                'style_id' => 30015,
            ];
            return http_get('https://i.snssdk.com/api/amos/spring_travel/covid/info?' . http_build_query($params))->body;
        } catch (\Throwable $throwable) {
            return $throwable->getMessage();
        }
    }

    public function ncovAction()
    {
        $city_name = input('city_name');
        try {
            $city_code = Province::value(['name' => $city_name], 'code');
            $params = [
                'city_code' => [(string)$city_code],
                'data_type' => [1],
                'src_type' => 'local',
            ];
            return http_get('https://i.snssdk.com/forum/ncov_data/?' . http_build_query($params))->body;
        } catch (\Throwable $throwable) {
            return $throwable->getMessage();
        }
    }

    public function districtStatAction()
    {
        $city_name = input('city_name');
        try {
            $city_code = Province::value(['name' => $city_name], 'code');
            $params = [
                'local_id' => $city_code
            ];
            $response = http_get('https://i.snssdk.com/toutiao/normandy/pneumonia_trending/district_stat/?' . http_build_query($params))->body;
            if ($response['status'] === 0 && isset($response['data']['list'])) {
                return $response['data']['list'];
            }
            throw new Exception('请求出错');
        } catch (\Throwable $throwable) {
            return $throwable->getMessage();
        }
    }
}
