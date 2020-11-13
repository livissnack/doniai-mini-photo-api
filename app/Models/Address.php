<?php
namespace App\Models;

use ManaPHP\Db\Model;

/**
 * Class App\Models\Address
 */
class Address extends Model
{
    public $address_id;
    public $user_id;
    public $country;
    public $province;
    public $city;
    public $area;
    public $region;
    public $receiver_phone;
    public $receiver;
    public $tag;
    public $is_default;
    public $updator_name;
    public $updated_time;
    public $creator_name;
    public $created_time;

    /**
     * @return string
     */
    public function getTable()
    {
        return 'address';
    }

    /**
     * @return string
     */
    public function getPrimaryKey()
    {
        return 'address_id';
    }
}