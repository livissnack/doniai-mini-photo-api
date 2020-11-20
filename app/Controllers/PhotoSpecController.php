<?php

namespace App\Controllers;

use App\Models\PhotoSpec;
use ManaPHP\Rest\Controller;

class PhotoSpecController extends Controller
{
    public function indexAction()
    {
        $keyword = input('keyword', ['string', 'default' => '']);
        $query = PhotoSpec::search(['spec_id', 'spec_name'])->where(['enabled' => 1]);
        if (!is_null($keyword)) {
            $query->whereContains('spec_name', $keyword);
        }
        return $query->all();

    }

    public function detailAction()
    {
        $address_id = input('photo_spec_id', ['int', 'default' => 0]);
        return PhotoSpec::get($address_id);
    }
}
