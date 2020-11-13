<?php

namespace ManaPHP;

use ManaPHP\Logger\LogCategorizable;

/**
 * Class Plugin
 *
 * @package ManaPHP
 *
 * @property-read \ManaPHP\Security\CaptchaInterface      $captcha
 * @property-read \ManaPHP\Http\RequestInterface          $request
 * @property-read \ManaPHP\Http\ResponseInterface         $response
 * @property-read \ManaPHP\DispatcherInterface            $dispatcher
 * @property-read \ManaPHP\Message\QueueInterface         $messageQueue
 * @property-read \ManaPHP\Db\Model\MetadataInterface     $modelsMetadata
 * @property-read \ManaPHP\Security\HtmlPurifierInterface $htmlPurifier
 * @property-read \ManaPHP\RouterInterface                $router
 * @property-read \ManaPHP\AuthorizationInterface         $authorization
 * @property-read \ManaPHP\Http\CookiesInterface          $cookies
 * @property-read \ManaPHP\Http\SessionInterface          $session
 * @property-read \ManaPHP\RendererInterface              $renderer
 */
abstract class Plugin extends Component implements PluginInterface, LogCategorizable
{
    public function categorizeLog()
    {
        return basename(str_replace('\\', '.', static::class), 'Plugin');
    }
}