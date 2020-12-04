<?php

namespace App\Controllers;

use App\Models\PhotoSpec;
use App\Models\User;
use App\Services\AliMarketService;
use App\Services\AliyunOssService;
use App\Services\BaiduService;
use App\Services\PhotoService;
use App\Services\TicketService;
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
 * @property-read TicketService $ticketService
 */
class TestController extends Controller
{
    public function getAcl()
    {
        return ['*' => '*'];
    }

    public function indexAction()
    {
        return $this->ticketService->doubleBall();

        $data = [
            ['title' => 2],
            ['title' => 12],
            ['title' => 13],
            ['title' => 14],
            ['title' => 15],
            ['title' => 16],

            ['title' => 18],
        ];

        $data = array_chunk($data, 6);
        $qianqu = implode(' ', array_column($data[0], 'title'));
        $houqu = implode( ' ', array_column($data[1], 'title'));
        return $qianqu;




        $a = ['1', '1'];
        foreach ($a as $k => $v) {
            $v = 2;
        }
        return $a;
        $b = [3, 4];
        $c = array_merge_recursive($a, $b);
        return $c;
        return password_hash('123', PASSWORD_DEFAULT);
        return $this->readLine();
    }

    public function explodeStr()
    {
        $str = '1,2,3';
        return explode(',', $str);
    }

    public function implodeArr()
    {
        $arr = [1, 2, 3];
//        return join(',', $arr);
        return implode(';', $arr);
    }

    /**
     * 逐行读取txt文件
     */
    public function readLine()
    {
        $file = fopen(path('@data/text.txt'), 'r');
        $arrs = [];
        $i = 0;
        while (!feof($file)) {
            $arrs[$i] = fgetc($file);
            $i++;
        }
        fclose($file);
        return $arrs;
    }

    /**
     * 输出倒序星星图
     */
    public function xing()
    {
        for ($i = 1; $i <= 8; $i++) {
            for ($s = 1; $s <= ((8 - $i) + 1); $s++) {
                echo '*';
            }
            echo '<br/>';
        }
    }

    /**
     * 如何解决多线程同时读写一个文件的问题
     */
    public function fileProblem()
    {
        $fp = fopen(path('@data/tencent.text'), 'w+');
        if (flock($fp, LOCK_EX)) {
            fwrite($fp, 'write here');
            flock($fp, LOCK_UN);
        } else {
            echo "cloudn't lock file";
        }
        fclose($fp);
    }

    public function alphFirst()
    {
        $str = 'aa_bb';
        $arr = explode('_', $str);

        $newArr = [];
        foreach ($arr as $k => $item) {
            $newArr[$k] = ucfirst($item);
        }
        return implode('', $newArr);
    }

    public function diffDays()
    {
        $day1 = '2016-10-5';
        $day2 = '2016-12-6';
        $seconds1 = strtotime($day1);
        $seconds2 = strtotime($day2);
        if ($seconds1 > $seconds2) {
            $diff = $seconds1 - $seconds2;
        } else {
            $diff = $seconds2 - $seconds1;
        }
        $days = $diff/86400;
        return $days;
    }

    public function sortArr()
    {
        $arr = [0 => 1, 'aa' => 2, 3, 4, 'bb' => [[5], [6, 7]]];

        $newArr = [];

        array_walk_recursive($arr, function ($x) use (&$newArr) {
            $newArr[] = $x;
        });
        sort($newArr);
        return join(',', $newArr);
    }

    public function getEmail()
    {
        $xml = "
            <ValueList>
                <Value>@163.comadasda</Value>
                <Value>Hello</Value>
                <Value>hahaha@163.com</Value>
                <Value>Green</Value>
            </ValueList>
        ";
        $pattern = "/\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*/";
        preg_match($pattern, $xml, $matches);
        return $matches[0];

    }
}
