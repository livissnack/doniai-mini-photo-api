<?php
namespace App\Models;

use ManaPHP\Db\Model;

/**
 * Class App\Models\Videos
 */
class Videos extends Model
{
    const STATUS_UP = 0;        //上架
    const STATUS_DOWN = 1;      //下架

    const TYPE_VIDEO = 0;       //普通视频
    const TYPE_LIVE = 1;        //直播流
    
    public $video_id;
    public $title;
    public $url;
    public $remark;
    public $cover_url;
    public $duration;
    public $play_nums;
    public $status;
    public $type;
    public $room_id;
    public $platform;
    public $is_crawler;
    public $category_id;
    public $created_at;
    public $updated_at;

    /**
     * @return string
     */
    public function getTable()
    {
        return 'videos';
    }

    /**
     * @return string
     */
    public function getPrimaryKey()
    {
        return 'video_id';
    }
}
