<?php

namespace App\Controllers;

use App\Models\Address;
use ManaPHP\Rest\Controller;

class AddressController extends Controller
{
    public function indexAction()
    {
        return Address::select(['address_id', 'user_id', 'country', 'province', 'city', 'area', 'region', 'receiver_phone', 'receiver', 'tag', 'is_default'])->all();
    }

    public function createAction()
    {
        $user_id = $this->identity->getId();
        if ($user_id < 0) {
            return '用户未登录';
        }
        return Address::rCreate(['user_id' => $user_id, 'country', 'province', 'city', 'area', 'region', 'receiver_phone', 'receiver', 'tag', 'is_default']);
    }

    public function editAction()
    {
        return Address::rUpdate(['country', 'province', 'city', 'area', 'region', 'receiver_phone', 'receiver', 'tag', 'is_default']);
    }

    public function detailAction()
    {
        $address_id = input('address_id', ['int', 'default' => 0]);
        return Address::get($address_id);
    }
}
