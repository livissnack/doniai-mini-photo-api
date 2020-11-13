<?php
namespace App\Models;

use ManaPHP\Db\Model;

/**
 * Class App\Models\PayLog
 */
class PayLog extends Model
{
    public $log_id;
    public $type_id;
    public $type_name;
    public $user_id;
    public $balance;
    public $amount;
    public $pay_info;
    public $remark;
    public $creator_name;
    public $updator_name;
    public $created_time;
    public $updated_time;

    /**
     * @return string
     */
    public function getTable()
    {
        return 'pay_log';
    }

    /**
     * @return string
     */
    public function getPrimaryKey()
    {
        return 'log_id';
    }
}