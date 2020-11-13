<?php
namespace App\Models;

use ManaPHP\Db\Model;

/**
 * Class App\Models\PayLogType
 */
class PayLogType extends Model
{
    public $id;
    public $code;
    public $name;

    /**
     * @return string
     */
    public function getTable()
    {
        return 'pay_log_type';
    }

    /**
     * @return string
     */
    public function getPrimaryKey()
    {
        return 'id';
    }
}