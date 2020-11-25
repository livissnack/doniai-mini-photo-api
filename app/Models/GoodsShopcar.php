<?php
namespace App\Models;

use ManaPHP\Db\Model;

/**
 * Class App\Models\GoodsShopcar
 */
class GoodsShopcar extends Model
{
    public $shopcar_id;
    public $user_id;
    public $goods_id;
    public $buy_nums;
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
        return 'goods_shopcar';
    }

    /**
     * @return string
     */
    public function getPrimaryKey()
    {
        return 'shopcar_id';
    }
}