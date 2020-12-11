<?php

namespace App\Controllers;

use App\Models\Clothes;
use ManaPHP\Rest\Controller;

class ClothesController extends Controller
{
    public function indexAction()
    {
        $sex = input('sex', ['int', 'default' => 1, 'in' => '1,2,3']);
        return Clothes::where(['sex' => $sex, 'enabled' => 1])
            ->select(['id', 'url', 'type'])
            ->all();
    }
}
