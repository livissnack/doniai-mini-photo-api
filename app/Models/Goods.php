<?php
namespace App\Models;

use ManaPHP\Db\Model;

/**
 * Class App\Models\Goods
 */
class Goods extends Model
{
    public $goods_id;
    public $name;
    public $images;
    public $price;
    public $description;
    public $nums;
    public $sale_nums;
    public $is_free;
    public $is_up;
    public $updator_name;
    public $updated_time;
    public $creator_name;
    public $created_time;

    /**
     * @return string
     */
    public function getTable()
    {
        return 'goods';
    }

    /**
     * @return string
     */
    public function getPrimaryKey()
    {
        return 'goods_id';
    }
}