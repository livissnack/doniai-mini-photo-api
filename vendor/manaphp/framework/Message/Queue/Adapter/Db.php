<?php

namespace ManaPHP\Message\Queue\Adapter;

use ManaPHP\Message\Queue;

/**
 * Class Db
 *
 * @package ManaPHP\Message\Queue\Adapter
 *
 *CREATE TABLE `manaphp_message_queue` (
 * `id` int(11) NOT NULL AUTO_INCREMENT,
 * `priority` tinyint(4) NOT NULL,
 * `topic` char(16) NOT NULL,
 * `body` varchar(4000) NOT NULL,
 * `created_time` int(11) NOT NULL,
 * `deleted_time` int(11) NOT NULL,
 * PRIMARY KEY (`id`)
 * ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8
 *
 */
class Db extends Queue
{
    /**
     * @var string
     */
    protected $_db = 'db';

    /**
     * @var string
     */
    protected $_source = 'manaphp_message_queue';

    /**
     *
     * Db constructor.
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        if (isset($options['db'])) {
            $this->_db = $options['db'];
        }

        if (isset($options['source'])) {
            $this->_source = $options['source'];
        }
    }

    /**
     * @param string $topic
     * @param string $body
     * @param int    $priority
     *
     * @return void
     */
    public function do_push($topic, $body, $priority = Queue::PRIORITY_NORMAL)
    {
        /** @var \ManaPHP\DbInterface $db */
        $db = $this->_di->getShared($this->_db);

        $db->insert($this->_source, ['topic' => $topic, 'body' => $body, 'priority' => $priority, 'created_time' => time(), 'deleted_time' => 0]);
    }

    /**
     * @param string $topic
     * @param int    $timeout
     *
     * @return string|false
     */
    public function do_pop($topic, $timeout = PHP_INT_MAX)
    {
        /** @var \ManaPHP\DbInterface $db */
        $db = $this->_di->getShared($this->_db);

        $startTime = time();

        $prev_max = null;
        do {
            $max_id = $db->query($this->_source)->max('id');
            if ($prev_max !== $max_id) {
                $prev_max = $max_id;

                $r = $db->query($this->_source)
                    ->where(['topic' => $topic, 'deleted_time' => 0])
                    ->orderBy(['priority' => SORT_ASC, 'id' => SORT_ASC])
                    ->first();

                if ($r && $db->update($this->_source, ['deleted_time' => time()], ['id' => $r['id']])) {
                    return $r['body'];
                }
            }
            sleep(1);
        } while (time() - $startTime < $timeout);

        return false;
    }

    /**
     * @param string $topic
     *
     * @return void
     */
    public function do_delete($topic)
    {
        /** @var \ManaPHP\DbInterface $db */
        $db = $this->_di->getShared($this->_db);

        $db->delete($this->_source, ['topic' => $topic]);
    }

    /**
     * @param string $topic
     * @param int    $priority
     *
     * @return int
     */
    public function do_length($topic, $priority = null)
    {
        /** @var \ManaPHP\DbInterface $db */
        $db = $this->_di->getShared($this->_db);

        if ($priority === null) {
            return $db->query($this->_source)->where(['topic' => $topic, 'deleted_time' => 0])->count();
        } else {
            return $db->query($this->_source)->where(['topic' => $topic, 'deleted_time' => 0, 'priority' => $priority])->count();
        }
    }
}