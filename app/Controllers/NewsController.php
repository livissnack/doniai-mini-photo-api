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
        $news = News::first(['news_id' => $news_id], ['news_id', 'title', 'article', 'author', 'source', 'pushed_time']);
        ++$news->see_nums;
        $news->save();
        return $news;
    }

    public function likeAction()
    {
        $news_id = input('news_id', ['int', 'default' => 0]);
        $news = News::get($news_id);
        ++$news->like_nums;
        $news->save();
        return $news;
    }
}
