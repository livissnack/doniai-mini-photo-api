<?php

namespace App\Controllers;

use App\Models\NavMenu;
use ManaPHP\Rest\Controller;

class MenuController extends Controller
{
    public function indexAction()
    {
        $keyword = input('keyword', ['string', 'default' => '']);
        $query = NavMenu::select(['menu_id', 'name', 'logo_type', 'icon_suffix', 'image_url', 'jump_url'])->where(['enabled' => 1]);
        if (!is_null($keyword)) {
            $query->whereContains('name', $keyword);
        }
        return $query->all();
    }
}
