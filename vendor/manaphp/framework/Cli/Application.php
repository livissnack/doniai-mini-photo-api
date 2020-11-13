<?php

namespace ManaPHP\Cli;

use ManaPHP\Exception\AbortException;
use ManaPHP\Logger\LogCategorizable;
use Swoole\Coroutine;
use Swoole\Event;
use Swoole\Runtime;
use Throwable;

/**
 * Class ManaPHP\Cli\Application
 *
 * @package application
 *
 * @property-read \ManaPHP\Cli\HandlerInterface $cliHandler
 */
class Application extends \ManaPHP\Application implements LogCategorizable
{
    /**
     * @var int
     */
    protected $_exit_code;

    /**
     * @return string
     */
    public function categorizeLog()
    {
        return 'cli';
    }

    /**
     * Application constructor.
     *
     * @param \ManaPHP\Loader $loader
     */
    public function __construct($loader = null)
    {
        define('MANAPHP_CLI', true);

        parent::__construct($loader);

        if ($appDir = $this->alias->get('@app')) {
            if (is_dir("$appDir/Cli")) {
                $this->alias->set('@cli', "$appDir/Cli/Controllers");
                $this->alias->set('@ns.cli', "App\\Cli\\Controllers");
            } elseif (($class = static::class) !== __CLASS__) {
                $this->alias->set('@cli', "$appDir/Controllers");
                $this->alias->set('@ns.cli', substr($class, 0, strrpos($class, '\\') + 1) . 'Controllers');
            }
        }
    }

    public function getDi()
    {
        if (!$this->_di) {
            $this->_di = new Factory();
        }
        return $this->_di;
    }

    public function handle()
    {
        $this->logger->info(['command line: :cmd', 'cmd' => basename($GLOBALS['argv'][0]) . ' ' . implode(' ', array_slice($GLOBALS['argv'], 1))]);

        try {
            $this->_exit_code = $this->cliHandler->handle();
        } catch (AbortException $exception) {
            $this->_exit_code = 0;
        } catch (\ManaPHP\Cli\Request\Exception $exception) {
            $this->_exit_code = 254;
            $this->errorHandler->handle($exception);
        } catch (Throwable $throwable) {
            $this->_exit_code = 255;
            $this->errorHandler->handle($throwable);
        }
    }

    public function main()
    {
        $this->dotenv->load();
        $this->configure->load();

        $this->registerServices();

        if (MANAPHP_COROUTINE_ENABLED) {
            Runtime::enableCoroutine(true);
            Coroutine::create([$this, 'handle']);
            Event::wait();
        } else {
            $this->handle();
        }

        exit($this->_exit_code);
    }
}