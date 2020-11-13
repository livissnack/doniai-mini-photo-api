<?php
namespace App\Models;

use ManaPHP\Db\Model;

/**
 * Class App\Models\User
 */
class User extends Model
{
    public $user_id;
    public $openid;
    public $unionid;
    public $session_key;
    public $avatarUrl;
    public $city;
    public $country;
    public $gender;
    public $nickName;
    public $province;
    public $balance;
    public $real_name;
    public $login_time;
    public $login_ip;
    public $updator_name;
    public $updated_time;
    public $creator_name;
    public $created_time;

    /**
     * @return string
     */
    public function getTable()
    {
        return 'user';
    }

    /**
     * @return string
     */
    public function getPrimaryKey()
    {
        return 'user_id';
    }
}
