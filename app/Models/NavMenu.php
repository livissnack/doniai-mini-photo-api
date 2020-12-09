<?php
namespace App\Models;

use ManaPHP\Db\Model;

/**
 * Class App\Models\NavMenu
 */
class NavMenu extends Model
{
    public $menu_id;
    public $name;
    public $logo_type;
    public $icon_suffix;
    public $image_url;
    public $jump_url;
    public $color;
    public $enabled;
    public $remark;
    public $creator_name;
    public $updator_name;
    public $created_time;
    public $updated_time;

    /**
     * @return string
     */
    public function getTable()
    {
        return 'nav_menu';
    }

    /**
     * @return string
     */
    public function getPrimaryKey()
    {
        return 'menu_id';
    }
}
