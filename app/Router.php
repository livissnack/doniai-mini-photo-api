<?php

namespace App;

use App\Controllers\UserController;

class Router extends \ManaPHP\Router
{
    public function __construct()
    {
        $this->_prefix = '/api';

        parent::__construct(true);

        $this->setAreas();

        $this->add('/', 'index::hello');
        $this->addGet('/time/current', [UserController::class, 'current']);
    }
}
