<?php

namespace App\Models;

use ManaPHP\Db\Model;

/**
 * Class App\Models\Order
 */
class Order extends Model
{
    const UNENABLED_SHOW = 0;   //不显示
    const ENABLED_SHOW = 1;     //显示

    const STATUS_UNPAY = 0;     //未支付
    const STATUS_PAYED = 1;     //已支付
    const STATUS_RETURN = 2;    //已退款
    const STATUS_TRANSIT = 3;   //运输中
    const STATUS_RECKONED = 4;  //已完成

    const TYPE_VIRTUAL = 0;  //虚拟订单
    const TYPE_INKIND = 1;  //实物订单

    public $order_id;
    public $order_sn;
    public $status;
    public $amount;
    public $total_amount;
    public $type;
    public $is_show;
    public $logistic_company;
    public $user_id;
    public $address_id;
    public $tracking_number;
    public $send_name;
    public $transit_time;
    public $delivery_time;
    public $good_name;
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

    public function create()
    {

        $this->order_sn = date('YmdHis', time()).bin2hex(random_bytes(4));

        return parent::create();
    }
}
