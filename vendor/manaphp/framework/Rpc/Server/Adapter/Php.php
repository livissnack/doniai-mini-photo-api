<?php

namespace ManaPHP\Rpc\Server\Adapter;

use ManaPHP\Helper\Ip;

/**
 * Class Php
 *
 * @package ManaPHP\Rpc\Server\Adapter
 *
 * @property-read \ManaPHP\RouterInterface $router
 */
class Php extends Fpm
{
    /**
     * Fpm constructor.
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        parent::__construct($options);

        $public_dir = $this->alias->resolve('@public');
        $local_ip = $this->_host === '0.0.0.0' ? Ip::local() : $this->_host;

        if (PHP_SAPI === 'cli') {
            if (DIRECTORY_SEPARATOR === '\\') {
                shell_exec("explorer.exe http://127.0.0.1:$this->_port" . ($this->router->getPrefix() ?: '/'));
            }
            $_SERVER['REQUEST_SCHEME'] = 'http';
            $index = @get_included_files()[0];
            $cmd = "php -S $this->_host:$this->_port -t $public_dir  $index";
            $this->log('info', $cmd);
            $this->log('info', "http://$local_ip:$this->_port" . ($this->router->getPrefix() ?: '/'));
            shell_exec($cmd);
            exit(0);
        } else {
            $_SERVER['SERVER_ADDR'] = $local_ip;
            $_SERVER['SERVER_PORT'] = $this->_port;
            $_SERVER['REQUEST_SCHEME'] = 'http';
            $_GET['_url'] = $_REQUEST['_url'] = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
        }
    }
}