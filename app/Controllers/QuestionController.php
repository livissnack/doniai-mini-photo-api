<?php

namespace App\Controllers;

use App\Models\Question;
use ManaPHP\Rest\Controller;

/**
 * Class QuestionController
 * @package App\Controllers
 */
class QuestionController extends Controller
{
    public function indexAction()
    {
       return Question::select(['question_id', 'title'])
           ->where(['is_show' => Question::ENABLED_SHOW])
           ->paginate();
    }

    public function detailAction()
    {
        $question_id = input('question_id', ['int', 'default' => 0]);
        return Question::value(['question_id' => $question_id], 'content');
    }
}
