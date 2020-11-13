<?php

namespace ManaPHP\Rpc;

use ManaPHP\Aop\Unaspectable;
use ManaPHP\Component;
use Throwable;

/**
 * Class Server
 *
 * @package ManaPHP\Rpc
 * @property-read \ManaPHP\Http\RequestInterface  $request
 * @property-read \ManaPHP\Http\ResponseInterface $response
 */
abstract class Server extends Component implements ServerInterface, Unaspectable
{
    /**
     * @var string
     */
    protected $_host = '0.0.0.0';

    /**
     * @var int
     */
    protected $_port = 9501;

    /**
     * @var \ManaPHP\Rpc\Server\HandlerInterface
     */
    protected $_handler;

    /**
     * Server constructor.
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        if (isset($options['host'])) {
            $this->_host = $options['host'];
        }

        if (isset($options['port'])) {
            $this->_port = (int)$options['port'];
        }
    }

    public function log($level, $message)
    {
        echo sprintf('[%s][%s]: ', date('c'), $level), $message, PHP_EOL;
    }

    /**
     * @return bool
     */
    public function authenticate()
    {
        try {
            if ($this->_handler->authenticate() !== false) {
                return true;
            }

            if (!$this->response->getContent()) {
                $this->response->setStatus(401)->setJsonContent(['code' => 401, 'message' => 'Unauthorized']);
            }
        } catch (Throwable $throwable) {
            $this->response->setJsonContent($throwable);
        }

        return false;
    }
}