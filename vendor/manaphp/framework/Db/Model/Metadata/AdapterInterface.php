<?php

namespace ManaPHP\Db\Model\Metadata;

/**
 * Interface ManaPHP\Mvc\Model\Metadata\AdapterInterface
 *
 * @package modelsMetadata
 */
interface AdapterInterface
{
    /**
     * Reads the meta-data from temporal memory
     *
     * @param string $key
     *
     * @return array|false
     */
    public function read($key);

    /**
     * Writes the meta-data to temporal memory
     *
     * @param string $key
     * @param array  $data
     *
     * @return void
     */
    public function write($key, $data);
}