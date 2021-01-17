<?php
namespace App\Models;

use ManaPHP\Db\Model;

/**
 * Class App\Models\Province
 */
class Province extends Model
{
    public $id;
    public $code;
    public $name;
    public $province;
    public $city;
    public $area;
    public $town;

    /**
     * @return string
     */
    public function getTable()
    {
        return 'province';
    }

    /**
     * @return string
     */
    public function getPrimaryKey()
    {
        return 'id';
    }
}