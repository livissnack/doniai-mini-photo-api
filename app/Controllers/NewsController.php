<?php

namespace App\Controllers;

use App\Models\News;
use ManaPHP\Rest\Controller;

class NewsController extends Controller
{
    public function indexAction()
    {
       return News::select(['news_id', 'title', 'article', 'author', 'source', 'pushed_time', 'post_image_url', 'see_nums', 'like_nums', 'tag'])
           ->where(['status' => News::STATUS_NORMAL])
           ->paginate();
    }

    public function detailAction()
    {
        $news_id = input('news_id', ['int', 'default' => 0]);
        return News::first(['news_id' => $news_id], ['news_id', 'title', 'article', 'author', 'source', 'pushed_time']);
    }
}
