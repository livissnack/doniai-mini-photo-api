<?php

namespace ManaPHP\Plugins;

use ManaPHP\Event\EventArgs;
use ManaPHP\Plugin;

class EtagPlugin extends Plugin
{
    /**
     * @var bool
     */
    protected $_enabled = true;

    /**
     * EtagPlugin constructor.
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        if (isset($options['enabled'])) {
            $this->_enabled = (bool)$options['enabled'];
        }

        if ($this->_enabled) {
            $this->attachEvent('response:sending', [$this, 'onResponseSending']);
        }
    }

    public function onResponseSending(EventArgs $eventArgs)
    {
        /** @var \ManaPHP\Http\ResponseContext $response */
        $response = $eventArgs->data['response'];
        if ($response->status_code !== 200 || !in_array($this->request->getMethod(), ['GET', 'HEAD'], true)) {
            return;
        }

        if (isset($response->headers['ETag'])) {
            $etag = $response->headers['ETag'];
        } else {
            $etag = strlen($response->content) . '-' . md5($response->content);
            $response->headers['ETag'] = $etag;
        }

        $if_none_match = $this->request->getServer('HTTP_IF_NONE_MATCH');
        if ($if_none_match === $etag) {
            $response->status_code = 304;
            $response->status_text = 'Not Modified';
        }
    }
}