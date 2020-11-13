<?php
namespace App\Models;

use ManaPHP\Db\Model;

/**
 * Class App\Models\Question
 */
class Question extends Model
{
    const UNENABLED_SHOW = 0;
    const ENABLED_SHOW = 1;

    public $question_id;
    public $title;
    public $content;
    public $is_show;
    public $updator_name;
    public $updated_time;
    public $creator_name;
    public $created_time;

    /**
     * @return string
     */
    public function getTable()
    {
        return 'question';
    }

    /**
     * @return string
     */
    public function getPrimaryKey()
    {
        return 'question_id';
    }
}
