<?php

namespace ManaPHP\Http\Server\Adapter;

use ManaPHP\Helper\Ip;
use ManaPHP\Http\Server;
use Swoole\Runtime;
use Throwable;

/** @noinspection PhpMultipleClassesDeclarationsInOneFile */

class SwooleContext
{
    /**
     * @var \Swoole\Http\Request
     */
    public $request;

    /**
     * @var \Swoole\Http\Response
     */
    public $response;
}

/**
 * Class Server
 *
 * @package ManaPHP\Http\Server
 * @property-read \ManaPHP\RouterInterface                   $router
 * @property-read \ManaPHP\Http\Server\Adapter\SwooleContext $_context
 */
class Swoole extends Server
{
    /**
     * @var array
     */
    protected $_settings = [];

    /**
     * @var \Swoole\Http\Server
     */
    protected $_swoole;

    /**
     * @var \ManaPHP\Http\Server\HandlerInterface
     */
    protected $_handler;

    /**
     * @var array
     */
    protected $_server;

    /**
     * Swoole constructor.
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        $script_filename = get_included_files()[0];
        $this->_server = [
            'DOCUMENT_ROOT' => dirname($script_filename),
            'SCRIPT_FILENAME' => $script_filename,
            'SCRIPT_NAME' => '/' . basename($script_filename),
            'SERVER_ADDR' => $this->_host === '0.0.0.0' ? Ip::local() : $this->_host,
            'SERVER_PORT' => $this->_port,
            'SERVER_SOFTWARE' => 'Swoole/' . SWOOLE_VERSION . ' PHP/' . PHP_VERSION,
            'PHP_SELF' => '/' . basename($script_filename),
            'QUERY_STRING' => '',
            'REQUEST_SCHEME' => 'http',
        ];


        $this->alias->set('@web', '');
        $this->alias->set('@asset', '');

        if (!isset($options['port'])) {
            $options['port'] = 9501;
        }

        $options['enable_coroutine'] = MANAPHP_COROUTINE_ENABLED;

        if (isset($options['max_request']) && $options['max_request'] < 1) {
            $options['max_request'] = 1;

            if (function_exists('opcache_reset')) {
                opcache_reset();
            }
        }

        if (!empty($options['enable_static_handler'])) {
            $options['document_root'] = $this->_server['DOCUMENT_ROOT'];
        }

        $this->_settings = $options;

        parent::__construct($options);

        if ($this->_use_globals) {
            $this->globalsManager->proxy();
        }

        $this->_swoole = new \Swoole\Http\Server($this->_host, $this->_port);
        $this->_swoole->set($this->_settings);
        $this->_swoole->on('Start', [$this, 'onStart']);
        $this->_swoole->on('ManagerStart', [$this, 'onManagerStart']);
        $this->_swoole->on('WorkerStart', [$this, 'onWorkerStart']);
        $this->_swoole->on('request', [$this, 'onRequest']);
    }

    /**
     * @param \Swoole\Http\Request $request
     */
    protected function _prepareGlobals($request)
    {
        $_server = array_change_key_case($request->server, CASE_UPPER);
        unset($_server['SERVER_SOFTWARE']);

        foreach ($request->header ?: [] as $k => $v) {
            if (in_array($k, ['content-type', 'content-length'], true)) {
                $_server[strtoupper(strtr($k, '-', '_'))] = $v;
            } else {
                $_server['HTTP_' . strtoupper(strtr($k, '-', '_'))] = $v;
            }
        }

        /** @noinspection AdditionOperationOnArraysInspection */
        $_server += $this->_server;

        $_get = $request->get ?: [];
        $request_uri = $_server['REQUEST_URI'];
        $_get['_url'] = ($pos = strpos($request_uri, '?')) ? substr($request_uri, 0, $pos) : $request_uri;

        $_post = $request->post ?: [];

        $globals = $this->request->getContext();

        if (!$_post && isset($_server['REQUEST_METHOD']) && !in_array($_server['REQUEST_METHOD'], ['GET', 'OPTIONS'], true)) {
            $globals->rawBody = $rowBody = $request->rawContent();

            if (isset($_server['CONTENT_TYPE']) && str_contains($_server['CONTENT_TYPE'], 'application/json')) {
                $_post = json_parse($rowBody);
            } else {
                parse_str($rowBody, $_post);
            }
            if (!is_array($_post)) {
                $_post = [];
            }
        }

        $this->request->setRequestId($_server['HTTP_X_REQUEST_ID'] ?? null);

        $globals->_GET = $_get;
        $globals->_POST = $_post;
        /** @noinspection AdditionOperationOnArraysInspection */
        $globals->_REQUEST = $_post + $_get;
        $globals->_SERVER = $_server;
        $globals->_COOKIE = $request->cookie ?: [];
        $globals->_FILES = $request->files ?: [];
    }

    /**
     * @param \Swoole\WebSocket\Server $server
     *
     * @noinspection PhpUnusedParameterInspection
     */
    public function onStart($server)
    {
        $title = sprintf('manaphp %s: master', $this->configure->id);

        @cli_set_process_title($title);
    }

    public function onManagerStart()
    {
        $title = sprintf('manaphp %s: manager', $this->configure->id);

        @cli_set_process_title($title);
    }

    /**
     * @param \Swoole\WebSocket\Server $server
     * @param int                      $worker_id
     *
     * @noinspection PhpUnusedParameterInspection
     */
    public function onWorkerStart($server, $worker_id)
    {
        $title = sprintf('manaphp %s: worker/%d', $this->configure->id, $worker_id);

        @cli_set_process_title($title);
    }

    /**
     * @param \ManaPHP\Http\Server\HandlerInterface $handler
     *
     * @return static
     */
    public function start($handler)
    {
        if (MANAPHP_COROUTINE_ENABLED) {
            Runtime::enableCoroutine(true);
        }

        echo PHP_EOL, str_repeat('+', 80), PHP_EOL;

        $this->_handler = $handler;

        $this->log('info',
            sprintf('starting listen on: %s:%d with setting: %s', $this->_host, $this->_port, json_stringify($this->_settings)));
        $this->log('info', 'http://' . $this->_server['SERVER_ADDR'] . ':' . $this->_server['SERVER_PORT'] . ($this->router->getPrefix() ?: '/'));

        $this->_swoole->start();
        echo sprintf('[%s][info]: shutdown', date('c')), PHP_EOL;

        return $this;
    }

    /**
     * @param \Swoole\Http\Request  $request
     * @param \Swoole\Http\Response $response
     */
    public function onRequest($request, $response)
    {
        try {
            if ($request->server['request_uri'] === '/favicon.ico') {
                $response->status(404);
                $response->end();
                return;
            }
            $context = $this->_context;

            $context->request = $request;
            $context->response = $response;

            $this->_prepareGlobals($request);

            $this->_handler->handle();
        } catch (Throwable $throwable) {
            $str = date('c') . ' ' . get_class($throwable) . ': ' . $throwable->getMessage() . PHP_EOL;
            $str .= '    at ' . $throwable->getFile() . ':' . $throwable->getLine() . PHP_EOL;
            $str .= preg_replace('/#\d+\s/', '    at ', $throwable->getTraceAsString());
            echo $str . PHP_EOL;
        }

        if (!MANAPHP_COROUTINE_ENABLED) {
            global $__root_context;

            if ($__root_context !== null) {
                foreach ($__root_context as $owner) {
                    unset($owner->_context);
                }

                $__root_context = null;
            }
        }
    }

    /**
     * @param \ManaPHP\Http\ResponseContext $response
     */
    public function send($response)
    {
        $this->fireEvent('response:sending', ['response' => $response]);

        $sw_response = $this->_context->response;

        $sw_response->status($response->status_code);

        foreach ($response->headers as $name => $value) {
            $sw_response->header($name, $value, false);
        }

        $server = $this->request->getContext()->_SERVER;

        $sw_response->header('X-Request-Id', $this->request->getRequestId(), false);
        $sw_response->header('X-Response-Time', sprintf('%.3f', microtime(true) - $server['REQUEST_TIME_FLOAT']), false);

        foreach ($response->cookies as $cookie) {
            $sw_response->cookie($cookie['name'], $cookie['value'], $cookie['expire'],
                $cookie['path'], $cookie['domain'], $cookie['secure'],
                $cookie['httpOnly']);
        }

        if ($response->status_code === 304) {
            $sw_response->end('');
        } elseif ($server['REQUEST_METHOD'] === 'HEAD') {
            $sw_response->header('Content-Length', strlen($response->content), false);
            $sw_response->end('');
        } elseif ($response->file) {
            $sw_response->sendfile($this->alias->resolve($response->file));
        } else {
            $sw_response->end($response->content);
        }

        $this->fireEvent('response:sent', ['response' => $response]);
    }
}
