<?php

namespace App\Controllers;

use App\Models\NavMenu;
use ManaPHP\Rest\Controller;

class MenuController extends Controller
{
    public function indexAction()
    {

        $keyword = input('keyword', ['string', 'default' => '']);
        if (is_null($keyword)) {
            if ($this->redisDb->get('doniai:navs')) {
                return json_parse($this->redisDb->get('doniai:navs'));
            }
        }
        $query = NavMenu::select(['menu_id', 'name', 'logo_type', 'icon_suffix', 'image_url', 'jump_url', 'color'])->where(['enabled' => 1]);
        if (!is_null($keyword)) {
            $query->whereContains('name', $keyword);
            return $query->all();
        } else {
            $data = $query->all();
            $this->redisDb->set('doniai:navs', json_stringify($data));
            return $data;
        }
    }
}
