<?php
namespace App\Models;

use ManaPHP\Db\Model;

/**
 * Class App\Models\News
 */
class News extends Model
{
    const STATUS_DISABLE = 0;   //禁用
    const STATUS_NORMAL = 1;    //正常
    const STATUS_DELETE = 2;    //删除

    public $news_id;
    public $title;
    public $article;
    public $author;
    public $source;
    public $status;
    public $post_image_url;
    public $see_nums;
    public $like_nums;
    public $tag;
    public $pushed_time;
    public $updator_name;
    public $updated_time;
    public $creator_name;
    public $created_time;

    /**
     * @return string
     */
    public function getTable()
    {
        return 'news';
    }

    /**
     * @return string
     */
    public function getPrimaryKey()
    {
        return 'news_id';
    }
}
