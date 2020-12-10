<?php

namespace App\Services;

use Throwable;
use ManaPHP\Service;
use ManaPHP\Exception\MissingFieldException;

class PhotoService extends Service
{

    public function verify($image_base64)
    {
        try {
            if (is_null($image_base64)) {
                throw new MissingFieldException('图片base64内容为空');
            }

            $request_body = [
                'app_key' => 'ef31bbe6bfedfda172421ff877cb294f03b24601',
                'file' => $image_base64,
                'facepose' => '40',
                'eyegaze' => '40',
                'eyeskew' => '35',
                'shoulderskew' => '20',
                'darkillum' => '50',
                'unbalanceillum' => '50',
                'bfsimilarity' => '60',
            ];
            $request_url = 'https://apicall.id-photo-verify.com/api/env_pic';
            $response = rest_post($request_url, $request_body)->body;
            if ($response['code'] === 200) {
                if ($response['total_result'] ===1) {
                    return true;
                }
                return $response;
            } else {
                throw new \Exception('证件照环境检测失败');
            }
        } catch (Throwable $throwable) {
            return $throwable->getMessage();
        }
    }

    public function make($image_base64, $spec_id=1)
    {
        try {
            if (is_null($image_base64)) {
                throw new MissingFieldException('图片base64内容为空');
            }

            $request_body = [
                'app_key' => 'ac461599dc482feda9cd0a65f66410d1a96d236c',
                'file' => $image_base64,
                'spec_id' => $spec_id,
                'is_fair' => 1,
                'fair_level' => 1
            ];
            $request_url = 'https://apicall.id-photo-verify.com/api/cut_pic';
            $response = rest_post($request_url, $request_body)->body;
            if ($response['code'] === 200) {
                return $response;
            } else {
                return $response['error'];
            }
        } catch (Throwable $throwable) {
            return $throwable->getMessage();
        }
    }

    public function take($filename)
    {
        try {
            if (is_null($filename)) {
                throw new MissingFieldException('图片名称为空');
            }

            $request_body = [
                'app_key' => 'ac461599dc482feda9cd0a65f66410d1a96d236c',
                'file_name' => $filename
            ];
            $request_url = 'https://apicall.id-photo-verify.com/api/take_cut_pic_v2';
            $response = rest_post($request_url, $request_body)->body;
            if ($response['data']['code'] === 200) {
                return $response['data'];
            } else {
                return $response['data']['error'];
            }
        } catch (Throwable $throwable) {
            return $throwable->getMessage();
        }
    }

    public function clothe($image_base64, $spec_id =1, $clothes = 'applet_boy1')
    {
        try {
            if (is_null($image_base64)) {
                throw new MissingFieldException('图片base64内容为空');
            }

            $clothes_list = [
                'applet_boy1',
                'applet_boy2',
                'applet_boy3',
                'applet_boy4',
                'applet_boy5',
                'applet_boy6',
                'applet_boy7',
                'applet_boy8',
                'applet_boy9',
                'applet_boy10',
                'applet_boy11',
                'applet_boy12',
                'applet_boy13',
                'applet_boy14',
                'applet_boy15',
                'applet_boy16',
                'applet_girl1',
                'applet_girl2',
                'applet_girl3',
                'applet_girl4',
                'applet_girl5',
                'applet_girl6',
                'applet_girl7',
                'applet_girl8',
                'applet_girl9',
                'applet_girl10',
                'applet_girl11',
                'applet_girl13',
                'applet_girl14',
                'applet_girl15',
                'applet_kid1',
                'applet_kid2',
                'applet_kid3',
                'applet_kid4',
                'applet_kid5',
                'applet_kid6',
                'applet_kid7',
                'applet_kid8',
                'applet_kid9',
                'applet_kid10',
                'applet_kid11',
                'applet_kid12',
                'applet_kid13',
                'applet_kid14',
                'applet_kid15',
                'applet_kid16',
                'applet_kid17',
                'applet_kid18',
                'applet_kid19',
                'applet_kid20',
            ];

            if (!in_array($clothes, $clothes_list, true)) {
                throw new MissingFieldException('该换装模板不支持');
            }

            $request_body = [
                'app_key' => 'd81a17c31b2ca2efd2ad5717e7b3446ff8b8fdd1',
                'file' => $image_base64,
                'spec_id' => $spec_id,
                'fair_level' => 0,
                'clothes' => $clothes
            ];
            $request_url = 'https://apicall.id-photo-verify.com/api/cut_change_clothes';
            $response = rest_post($request_url, $request_body)->body;
            if ($response['code'] === 200) {
                return $response;
            } else {
                return $response['error'];
            }
        } catch (Throwable $throwable) {
            return $throwable->getMessage();
        }
    }

    /**
     * @param $filename
     * @param int $hat_id
     * @param int $clothe_id
     * @return array|mixed|string
     * 全身（半身）换装
     */
    public function whole_body_clothe($filename, $hat_id =1, $clothe_id = 1)
    {
        try {
            if (is_null($filename)) {
                throw new MissingFieldException('图片地址为空');
            }

            $request_body = [
                'app_key' => '0d258d82617869442a54ff41f6bdd3f090b880ff',
                'file' => $filename,
                'clothes_id' => $clothe_id,
                'hat_id' => $hat_id,
                'need_resize' => true
            ];
            $request_url = 'http://apicall.id-photo-verify.com/api/whole_body_change';
            $response = rest_post($request_url, $request_body)->body;
            if ($response['code'] === 200) {
                return $response;
            } else {
                return $response['error'];
            }
        } catch (Throwable $throwable) {
            return $throwable->getMessage();
        }
    }


    public function cutout_pic($filename, $hat_id =1, $clothe_id = 1)
    {
        try {
            if (is_null($filename)) {
                throw new MissingFieldException('图片地址为空');
            }

            $request_body = [
                'app_key' => '0d258d82617869442a54ff41f6bdd3f090b880ff',
                'file' => $filename,
                'clothes_id' => $clothe_id,
                'hat_id' => $hat_id,
                'need_resize' => true
            ];
            $request_url = 'https://api.id-photo-verify.com/official_web/bgcolor';
            $response = rest_post($request_url, $request_body)->body;
            if ($response['code'] === 200) {
                return $response;
            } else {
                return $response['error'];
            }
        } catch (Throwable $throwable) {
            return $throwable->getMessage();
        }
    }

    public function spec($spec_id)
    {
        try {
            if (is_null($spec_id)) {
                throw new MissingFieldException('规格ID不能为空');
            }

            $request_url = 'https://apicall.id-photo-verify.com/api/get_specs';
            $response = rest_get($request_url.'/'.$spec_id)->body;
            if ($response['code'] === 200) {
                return $response;
            } else {
                return $response['error'];
            }
        } catch (Throwable $throwable) {
            return $throwable->getMessage();
        }
    }
}
