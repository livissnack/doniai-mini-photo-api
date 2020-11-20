<?php
namespace App\Models;

use ManaPHP\Db\Model;

/**
 * Class App\Models\PhotoSpec
 */
class PhotoSpec extends Model
{
    public $photo_spec_id;
    public $spec_name;
    public $spec_id;
    public $price;
    public $is_you;
    public $is_hot;
    public $enabled;
    public $height_mm;
    public $width_mm;
    public $height_px;
    public $width_px;
    public $ppi;
    public $is_print;
    public $file_size_max;
    public $file_size_min;
    public $background_color;
    public $updator_name;
    public $updated_time;
    public $creator_name;
    public $created_time;

    /**
     * @return string
     */
    public function getTable()
    {
        return 'photo_spec';
    }

    /**
     * @return string
     */
    public function getPrimaryKey()
    {
        return 'photo_spec_id';
    }
}
