<?php
namespace App\Models;

use ManaPHP\Db\Model;

/**
 * Class App\Models\Sign
 */
class Sign extends Model
{
    public $sign_id;
    public $user_id;
    public $sign_date;
    public $continue_days;
    public $updator_name;
    public $updated_time;
    public $creator_name;
    public $created_time;

    /**
     * @return string
     */
    public function getTable()
    {
        return 'sign';
    }

    /**
     * @return string
     */
    public function getPrimaryKey()
    {
        return 'sign_id';
    }
}