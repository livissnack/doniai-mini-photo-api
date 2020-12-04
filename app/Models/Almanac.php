<?php
namespace App\Models;

use ManaPHP\Db\Model;

/**
 * Class App\Models\Almanac
 */
class Almanac extends Model
{
    public $almanac_id;
    public $suitable;
    public $taboo;
    public $good_luck;
    public $ferocious;
    public $current_date;
    public $updator_name;
    public $updated_time;
    public $creator_name;
    public $created_time;

    /**
     * @return string
     */
    public function getTable()
    {
        return 'almanac';
    }

    /**
     * @return string
     */
    public function getPrimaryKey()
    {
        return 'almanac_id';
    }
}