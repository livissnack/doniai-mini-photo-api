<?php
namespace App\Models;

use ManaPHP\Db\Model;

/**
 * Class App\Models\PhotoBg
 */
class PhotoBg extends Model
{
    public $bg_id;
    public $name;
    public $value;
    public $enabled;
    public $updator_name;
    public $updated_time;
    public $creator_name;
    public $created_time;

    /**
     * @return string
     */
    public function getTable()
    {
        return 'photo_bg';
    }

    /**
     * @return string
     */
    public function getPrimaryKey()
    {
        return 'bg_id';
    }
}