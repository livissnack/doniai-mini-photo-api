<?php
namespace App\Models;

use ManaPHP\Db\Model;

/**
 * Class App\Models\Clothes
 */
class Clothes extends Model
{
    public $id;
    public $url;
    public $type;
    public $enabled;
    public $remark;
    public $mark;
    public $sex;
    public $updator_name;
    public $updated_time;
    public $creator_name;
    public $created_time;

    /**
     * @return string
     */
    public function getTable()
    {
        return 'clothes';
    }

    /**
     * @return string
     */
    public function getPrimaryKey()
    {
        return 'id';
    }
}
