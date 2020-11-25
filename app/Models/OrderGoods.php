<?php
namespace App\Models;

use ManaPHP\Db\Model;

/**
 * Class App\Models\OrderGoods
 */
class OrderGoods extends Model
{
    public $og_id;
    public $order_id;
    public $goods_id;
    public $goods_nums;
    public $goods_price;
    public $status;
    public $updator_name;
    public $updated_time;
    public $creator_name;
    public $created_time;

    /**
     * @return string
     */
    public function getTable()
    {
        return 'order_goods';
    }

    /**
     * @return string
     */
    public function getPrimaryKey()
    {
        return 'og_id';
    }
}