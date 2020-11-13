<?php

namespace ManaPHP\Message\PubSub\Adapter;

use ManaPHP\Component;
use ManaPHP\Message\PubSubInterface;

class Redis extends Component implements PubSubInterface
{
    /** @noinspection PhpUnusedParameterInspection */
    public function subscribe($channels, $callback)
    {
        $this->redisBroker->subscribe($channels, static function ($redis, $chan, $msg) use ($callback) {
            $callback($chan, $msg);
        });
    }

    /** @noinspection PhpUnusedParameterInspection */
    public function psubscribe($patterns, $callback)
    {
        $this->redisBroker->psubscribe($patterns, static function ($redis, $pattern, $chan, $msg) use ($callback) {
            $callback($chan, $msg);
        });
    }

    public function publish($channel, $message)
    {
        return $this->redisBroker->publish($channel, $message);
    }

    public function unsubscribe($channels = null)
    {
        $this->redisBroker->unsubscribe($channels);
    }

    public function punsubscribe($patterns = null)
    {
        $this->redisBroker->punsubscribe($patterns);
    }
}
