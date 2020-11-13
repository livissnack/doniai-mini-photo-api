<?php

namespace App;

class Application extends \ManaPHP\Rest\Application
{
    public function authenticate()
    {
        if ($token = $this->request->getToken()) {
            $this->identity->setClaims(jwt_decode($token, 'user'));
        }
    }

    public function authorize()
    {
        // parent::authorize();
    }
}
