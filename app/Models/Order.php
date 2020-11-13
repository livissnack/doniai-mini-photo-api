<?php
namespace App\Models;

use ManaPHP\Db\Model;

/**
 * Class App\Models\Order
 */
class Order extends Model
{
    const UNENABLED_SHOW = 0;
    const ENABLED_SHOW = 1;

    public $order_id;
    public $order_sn;
    public $status;
    public $amount;
    public $total_amount;
    public $type;
    public $is_show;
    public $logistic_company;
    public $tracking_number;
    public $send_name;
    public $transit_time;
    public $delivery_time;
    public $remark;
    public $updator_name;
    public $updated_time;
    public $creator_name;
    public $created_time;

    /**
     * @return string
     */
    public function getTable()
    {
        return 'order';
    }

    /**
     * @return string
     */
    public function getPrimaryKey()
    {
        return 'order_id';
    }
}
