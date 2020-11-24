<?php

namespace App\Services;

use ManaPHP\Exception\InvalidValueException;
use ManaPHP\Service;

class WechatService extends Service
{
    public function login($code)
    {
        try {
            if (is_null($code)) {
                throw new InvalidValueException('登录凭证code为空');
            }
            $params = [
                'appid' => param_get('wechat_mini_app_id'),
                'secret' => param_get('wechat_mini_app_secret'),
                'js_code' => $code,
                'grant_type' => 'authorization_code',
            ];
            $request_url = param_get('wechat_base_url') . '/sns/jscode2session?' . http_build_query($params);
            $response = rest_get($request_url, [])->body;
            $this->logger->info($response);
            return $response;
        } catch (\Throwable $throwable) {
            return $throwable->getMessage();
        }
    }



    public function ocr_printed_text($img_url)
    {
        try {
            $access_token = $this->_token();
            $params = [
                'img_url' => $img_url,
                'access_token' => $access_token
            ];
            $request_url = param_get('wechat_base_url') . '/cv/ocr/comm?' . http_build_query($params);
            $response = rest_post($request_url, [], [])->body;
            if (isset($response['errcode']) && $response['errcode'] === 0) {
                return $response['items'];
            } else {
                throw new \Exception('OCR图片识别失败');
            }
        } catch (\Throwable $throwable) {
            return $throwable->getMessage();
        }
    }

    public function trans($q, $from='zh-CN', $to='en_US')
    {
        try {
            if (is_null($q)) {
                throw new InvalidValueException('要翻译的内容为空');
            }
            $access_token = $this->_token();
            $request_url = param_get('wechat_base_url') . '/cgi-bin/media/voice/translatecontent?' . http_build_query(['access_token' => $access_token, 'lfrom' => $from, 'lto' => $to]);
            $response = rest_post($request_url, $q)->body;
            if (!isset($response['errcode'])) {
                return $response;
            } else {
                throw new \Exception('腾讯AI翻译接口调用失败');
            }
        } catch (\Throwable $throwable) {
            return $throwable->getMessage();
        }
    }

    /**
     * @param $temp_msg
     * @return array|string
     * {
     *       "touser":"OPENID",
     *       "weapp_template_msg":{
     *           "template_id":"TEMPLATE_ID",
     *           "page":"page/page/index",
     *           "form_id":"FORMID",
     *           "data":{
     *               "keyword1":{
     *               "value":"339208499"
     *           },
     *           "keyword2":{
     *               "value":"2015年01月05日 12:30"
     *           },
     *           "keyword3":{
     *               "value":"腾讯微信总部"
     *           },
     *           "keyword4":{
     *               "value":"广州市海珠区新港中路397号"
     *           }
     *       },
     *           "emphasis_keyword":"keyword1.DATA"
     *       }
     *  }
     */
    public function send_service_msg($temp_msg)
    {
        try {
            if (is_null($temp_msg)) {
                throw new InvalidValueException('要发送的服务消息为空');
            }
            $access_token = $this->_token();

            $request_url = param_get('wechat_base_url') . '/cgi-bin/message/wxopen/template/uniform_send?' . http_build_query(['access_token' => $access_token]);
            $response = rest_post($request_url, $temp_msg)->body;
            if (!isset($response['errcode']) && $response['errcode'] !== 0) {
                return $response;
            } else {
                throw new \Exception('发送服务消息接口调用失败');
            }
        } catch (\Throwable $throwable) {
            return $throwable->getMessage();
        }
    }

    public function send_custom_msg($cus_msg)
    {
        try {
            if (is_null($cus_msg)) {
                throw new InvalidValueException('要发送的客服消息为空');
            }
            $access_token = $this->_token();

            $request_url = param_get('wechat_base_url') . '/cgi-bin/message/custom/send?' . http_build_query(['access_token' => $access_token]);
            $response = rest_post($request_url, $cus_msg)->body;
            if (!isset($response['errcode']) && $response['errcode'] !== 0) {
                return $response;
            } else {
                throw new \Exception('发送客服消息接口调用失败');
            }
        } catch (\Throwable $throwable) {
            return $throwable->getMessage();
        }
    }

    public function send_subscribe_msg($cus_msg)
    {
        try {
            if (is_null($cus_msg)) {
                throw new InvalidValueException('要发送的客服消息为空');
            }
            $access_token = $this->_token();

            $request_url = param_get('wechat_base_url') . '/cgi-bin/message/subscribe/send?' . http_build_query(['access_token' => $access_token]);
            $response = rest_post($request_url, $cus_msg)->body;
            if (!isset($response['errcode']) && $response['errcode'] !== 0) {
                return $response;
            } else {
                throw new \Exception('发送客服消息接口调用失败');
            }
        } catch (\Throwable $throwable) {
            return $throwable->getMessage();
        }
    }

    public function daily_visit_trend()
    {
        try {
            $begin_date = $end_date = date('Ymd', time() - seconds('1d'));
            if (is_null($begin_date) || is_null($end_date)) {
                throw new InvalidValueException('查询区间日期为空');
            }
            $access_token = $this->_token();

            $request_url = param_get('wechat_base_url') . '/datacube/getweanalysisappiddailyvisittrend?' . http_build_query(['access_token' => $access_token]);
            $response = rest_post($request_url, ['begin_date' => $begin_date, 'end_date' => $end_date])->body;
            if (!isset($response['errcode'])) {
                return $response;
            } else {
                throw new \Exception('发送客服消息接口调用失败');
            }
        } catch (\Throwable $throwable) {
            return $throwable->getMessage();
        }
    }

    public function month_visit_trend()
    {
        try {
            $begin_date = date('Ym01', time() - seconds('1M'));
            $end_date = date('Ymt', time() - seconds('1M'));
            if (is_null($begin_date) || is_null($end_date)) {
                throw new InvalidValueException('查询区间日期为空');
            }
            $access_token = $this->_token();

            $request_url = param_get('wechat_base_url') . '/datacube/getweanalysisappidmonthlyvisittrend?' . http_build_query(['access_token' => $access_token]);
            $response = rest_post($request_url, ['begin_date' => $begin_date, 'end_date' => $end_date])->body;
            if (!isset($response['errcode'])) {
                return $response;
            } else {
                throw new \Exception('发送客服消息接口调用失败');
            }
        } catch (\Throwable $throwable) {
            return $throwable->getMessage();
        }
    }

    private function _token()
    {
        try {
            $redis_key = 'wechat:access_token';

            $access_token = $this->redisDb->get($redis_key);
            if ($access_token !== false) {
                return $access_token;
            }
            $params = [
                'appid' => param_get('wechat_mini_app_id'),
                'secret' => param_get('wechat_mini_app_secret'),
                'grant_type' => 'client_credential',
            ];
            $request_url = param_get('wechat_base_url') . '/cgi-bin/token?' . http_build_query($params);
            $response = rest_get($request_url)->body;
            if (!isset($response['errcode'])) {
                $this->redisDb->set($redis_key, $response['access_token'], seconds('2h'));
                return $response;
            } else {
                throw new \Exception('小程序授权登录失败');
            }
        } catch (\Throwable $throwable) {
            return $throwable->getMessage();
        }
    }
}
