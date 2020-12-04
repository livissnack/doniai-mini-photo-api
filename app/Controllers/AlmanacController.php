<?php

namespace App\Controllers;

use App\Models\Almanac;
use ManaPHP\Rest\Controller;

class AlmanacController extends Controller
{
    public function detailAction()
    {
        $current_date = input('current_date', ['int']);
        return Almanac::where(['current_date' => $current_date])->select(['almanac_id', 'suitable', 'taboo', 'good_luck', 'ferocious', 'current_date'])->first();
    }
}
