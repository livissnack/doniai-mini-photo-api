<?php

namespace App\Controllers;

use App\Models\PhotoSpec;
use ManaPHP\Rest\Controller;

class PhotoSpecController extends Controller
{
    public function indexAction()
    {
        $keyword = input('keyword', ['string', 'default' => '']);
        $query = PhotoSpec::search(['spec_id', 'title'])->where(['enabled' => 1]);
        if (!is_null($keyword)) {
            $query->whereContains('title', $keyword);
        }
        return $query->select(['photo_spec_id', 'title', 'size', 'price', 'is_you', 'is_hot'])->all();

    }

    public function detailAction()
    {
        $address_id = input('photo_spec_id', ['int', 'default' => 0]);
        return PhotoSpec::get($address_id);
    }
}
