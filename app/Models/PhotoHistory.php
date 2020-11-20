<?php
namespace App\Models;

use ManaPHP\Db\Model;

/**
 * Class App\Models\PhotoHistory
 */
class PhotoHistory extends Model
{
    public $ph_id;
    public $image_url;
    public $size;
    public $user_id;
    public $print_image_url;
    public $photo_key;
    public $spec_id;
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
        return 'photo_history';
    }

    /**
     * @return string
     */
    public function getPrimaryKey()
    {
        return 'ph_id';
    }
}
