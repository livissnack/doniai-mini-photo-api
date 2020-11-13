<?php

namespace ManaPHP\Db\Model\Metadata\Adapter;

use ManaPHP\Db\Model\Metadata;
use ManaPHP\Exception\ExtensionNotInstalledException;

/**
 * Class ManaPHP\Mvc\Model\Metadata\Adapter\Apcu
 *
 * @package modelsMetadata\adapter
 */
class Apcu extends Metadata
{
    /**
     * @var string
     */
    protected $_prefix = 'models_metadata:';

    /**
     * @var int
     */
    protected $_ttl = 86400;

    /**
     * Apcu constructor.
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        if (!extension_loaded('apcu')) {
            throw new ExtensionNotInstalledException('apcu');
        }

        if (isset($options['prefix'])) {
            $this->_prefix .= $options['prefix'];
        }

        if (isset($options['ttl'])) {
            $this->_ttl = $options['ttl'];
        }
    }

    /**
     * @param string $key
     *
     * @return array|false
     */
    public function read($key)
    {
        return apcu_fetch($this->_prefix . $key);
    }

    /**
     * @param string $key
     * @param array  $data
     *
     * @return void
     */
    public function write($key, $data)
    {
        apcu_store($this->_prefix . $key, $data, $this->_ttl);
    }
}