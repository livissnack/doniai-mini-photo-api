<?php

namespace App\Services;

use ManaPHP\Service;
use App\Models\Ticket;

/**
 * Class TicketService
 * @package App\Services
 */
class TicketService extends Service
{
    public function doubleBall()
    {
        //数组
        $ball['red'] = [];
        $ball['blue'] = [];
        $count = 0;
        //随机去除最近十期的球数，此处可以用个接口或者爬虫获取
        //最近十期红球
        $last_red = $this->getRecentRed(10);
        //最近十期蓝球
        $last_blue = $this->getRecentBlue(10);
        //生成6位数
        do {
            $rand_red = rand(1, 33);
            $appear = 0; //出现次数
            $contiue = 0; //连续出现次数
            //根据过往十期判断出现次数超过三次的忽略，连续超过两次的忽略
            foreach ($last_red as $key => $val) {
                if (in_array($rand_red, $val, true)) {
                    $appear++;
                    if ($key > 0) {
                        if (in_array($rand_red, $last_red[$key - 1], true))
                            $contiue++;
                    }
                }
            }
            //加入数组 十期判断出现次数超过三次的忽略，连续超过两次的忽略
            if (!in_array($rand_red, $ball['red'], true) && $appear < 3 && $contiue < 2) {
                $ball['red'][] = $rand_red;
            }
            //判断数量
            $count = count($ball['red']);
            if ($count == 6) {
                $type = 0;
            } else {
                $type = 1;
            }
        } while ($type);
        //冒泡排序
        for ($i = 0; $i < count($ball['red']); $i++) {
            for ($j = $i + 1; $j < count($ball['red']); $j++) {
                if ($ball['red'][$i] > $ball['red'][$j]) {
                    $item = $ball['red'][$j];
                    $ball['red'][$j] = $ball['red'][$i];
                    $ball['red'][$i] = $item;
                }
            }
        }
        //随机蓝色球
        $rand_blue = 0;
        do {
            $rand_blue = rand(1, 16);
            if (!in_array($rand_blue, $last_blue, true)) {
                $type = 0;
            } else {
                $type = 1;
            }
        } while ($type);
        //随机蓝球
        $ball['blue'] = $rand_blue;
        return ['red' => $ball['red'], 'blue' => $ball['blue']];
    }

    public function getRecentBlue($phase = 10)
    {
        $redis_recent_data = $this->redisDb->get('shuangseqiu:recent:ten:red');
        if (!$redis_recent_data) {
            $tickets = Ticket::select(['houqu'])->orderBy(['phase' => SORT_DESC])->limit($phase)->all();
            $recent_data = array_map('intval', array_column($tickets, 'houqu'));
            $this->redisDb->setex('shuangseqiu:recent:ten:red', seconds('1d'), json_stringify($recent_data));
            return $recent_data;
        }
        return json_parse($redis_recent_data);
    }

    public function getRecentRed($phase = 10)
    {
        $redis_recent_data = $this->redisDb->get('shuangseqiu:recent:ten:blue');
        if (!$redis_recent_data) {
            $tickets = Ticket::select(['qianqu'])->orderBy(['phase' => SORT_DESC])->limit($phase)->all();
            $blues = array_column($tickets, 'qianqu');
            $recent_data = array_map(function ($value) {
                return array_map('intval', explode(' ', $value));
            }, $blues);
            $this->redisDb->setex('shuangseqiu:recent:ten:blue', seconds('1d'), json_stringify($recent_data));
            return $recent_data;
        }
        return json_parse($redis_recent_data);
    }
}
