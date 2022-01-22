<?php

namespace App\Controllers;

use App\Models\Videos;
use ManaPHP\Rest\Controller;

class LiveController extends Controller
{
    public function indexAction()
    {
       return Videos::select(['video_id', 'title', 'url', 'cover_url', 'play_nums'])
           ->where([
               'status' => Videos::STATUS_UP,
               'type' => Videos::TYPE_LIVE
            ])
           ->paginate();
    }
}
