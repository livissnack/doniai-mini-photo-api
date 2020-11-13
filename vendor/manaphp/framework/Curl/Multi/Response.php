<?php

namespace ManaPHP\Curl\Multi;

class Response
{
    /**
     * @var int
     */
    public $http_code;

    /**
     * @var string
     */
    public $body;

    /**
     * @var array
     */
    public $headers = [];

    /**
     * @var string
     */
    public $file;

    /**
     * @var float
     */
    public $process_time;

    /**
     * @var string
     */
    public $content_type;

    /**
     * @var array
     */
    public $stats;

    /**
     * @var \ManaPHP\Curl\Multi\Request
     */
    public $request;
}