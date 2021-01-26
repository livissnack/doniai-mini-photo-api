<?php

namespace App\Controllers;

use ManaPHP\Rest\Controller;

class NameController extends Controller
{
    public function createAction()
    {
        $first_name = input('first_name');
        $m = input('month', ['int', 'range' => '1-12']);
        $d = input('day', ['int', 'range' => '1-31']);
        $one = ['真', '踏', '凝', '竹', '若', '雨', '紫', '影', '亦', '伊', '羽', '冰'];
        $two = ['菲', '星', '锦', '悠', '馨', '香', '爱', '眠', '落', '轩', '儿', '萱', '雪', '月', '芷', '凌', '珣', '痕', '荫', '茹', '忆', '忊', '舞', '琦', '汐', '熏', '郁', '心', '韵', '然', '嫣'];
        return $first_name . $one[$m - 1] . $two[$d - 1];
    }
}
