<?php

namespace ManaPHP;

use ManaPHP\Exception\InvalidValueException;
use ManaPHP\Exception\MisuseException;
use ManaPHP\Helper\LocalFS;

/** @noinspection PhpMultipleClassesDeclarationsInOneFile */

class ViewContext
{
    /**
     * @var int
     */
    public $max_age;

    /**
     * @var false|string|null
     */
    public $layout;

    /**
     * @var array
     */
    public $vars = [];

    /**
     * @var string
     */
    public $content;
}

/**
 * Class ManaPHP\View
 *
 * @package view
 *
 * @property-read \ManaPHP\RendererInterface   $renderer
 * @property-read \ManaPHP\DispatcherInterface $dispatcher
 * @property-read \ManaPHP\ViewContext         $_context
 */
class View extends Component implements ViewInterface
{
    /**
     * @var int
     */
    protected $_max_age;

    /**
     * @var string
     */
    protected $_base_url;

    /**
     * @var bool
     */
    protected $_autofix_url;

    /**
     * @var array
     */
    protected $_dirs = [];

    /**
     * View constructor.
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        if (isset($options['max_age'])) {
            $this->_max_age = (int)$options['max_age'];
        }

        $this->_base_url = rtrim($options['base_url'] ?? $this->alias->resolve('@web'));
        $this->_autofix_url = $options['autofix_url'] ?? $this->_base_url !== '';
    }

    /**
     * @param int $max_age
     *
     * @return static
     */
    public function setMaxAge($max_age)
    {
        $this->_context->max_age = (int)$max_age;

        return $this;
    }

    /**
     * @return int
     */
    public function getMaxAge()
    {
        if ($this->_max_age > 0) {
            $context = $this->_context;
            if ($context->max_age === null) {
                return $this->_max_age;
            } else {
                return $context->max_age > 0 ? $context->max_age : 0;
            }
        } else {
            return 0;
        }
    }

    /**
     * @param false|string $layout
     *
     * @return static
     */
    public function setLayout($layout = 'Default')
    {
        $context = $this->_context;

        $context->layout = $layout;

        return $this;
    }

    /**
     * Set a single view parameter
     *
     * @param string $name
     * @param mixed  $value
     *
     * @return static
     */
    public function setVar($name, $value)
    {
        $context = $this->_context;

        $context->vars[$name] = $value;

        return $this;
    }

    /**
     * Adds parameters to view
     *
     * @param array $vars
     *
     * @return static
     */
    public function setVars($vars)
    {
        $context = $this->_context;

        $context->vars = array_merge($context->vars, $vars);

        return $this;
    }

    /**
     * Returns a parameter previously set in the view
     *
     * @param string $name
     *
     * @return mixed
     */
    public function getVar($name = null)
    {
        $context = $this->_context;

        if ($name === null) {
            return $context->vars;
        } else {
            return $context->vars[$name] ?? null;
        }
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasVar($name)
    {
        $context = $this->_context;

        return isset($context->_vars[$name]);
    }

    /**
     * @param string $template
     * @param array  $vars
     * @param bool   $directOutput
     *
     * @return string
     */
    protected function _render($template, $vars, $directOutput)
    {
        if ($vars) {
            $this->setMaxAge(0);
        }

        if (isset($vars['view'])) {
            throw new MisuseException('variable `view` is reserved for view');
        }
        $vars['view'] = $this;

        return $this->renderer->render($template, $vars, $directOutput);
    }

    /**
     * @return string
     */
    protected function _findLayout()
    {
        $context = $this->_context;

        if ($context->layout === null) {
            $controller = $this->dispatcher->getController();
            if ($area = $this->dispatcher->getArea()) {
                if ($this->renderer->exists("@app/Areas/$area/Views/Layouts/$controller")) {
                    $layout = "@app/Areas/$area/Views/Layouts/$controller";
                } elseif ($this->renderer->exists("@app/Areas/$area/Views/Layouts/Default")) {
                    $layout = "@app/Areas/$area/Views/Layouts/Default";
                } else {
                    $layout = '@views/Layouts/Default';
                }
            } else {
                $layout = $this->renderer->exists("@views/Layouts/$controller") ? "@views/Layouts/$controller" : '@views/Layouts/Default';
            }
        } elseif (is_string($context->layout)) {
            $layout = $context->layout;
            if ($layout[0] !== '@') {
                $layout = ucfirst($layout);
                if (($area = $this->dispatcher->getArea()) && $this->renderer->exists("@app/Areas/$area/Views/Layouts/$layout")) {
                    $layout = "@app/Areas/$area/Views/Layouts/$layout";
                } else {
                    $layout = "@views/Layouts/$layout";
                }
            }
        } else {
            $layout = false;
        }

        return $layout;
    }

    /**
     * Executes render process from dispatching data
     *
     * @param string $template
     * @param array  $vars
     *
     * @return string
     */
    public function render($template = null, $vars = null)
    {
        $context = $this->_context;

        if ($vars !== null) {
            $context->vars = $vars;
        }

        if ($template === null) {
            $action = $this->dispatcher->getAction();
        } elseif (str_contains($template, '/')) {
            $action = null;
        } else {
            $action = $template;
            $template = null;
        }

        if ($template === null) {
            $area = $this->dispatcher->getArea();
            $controller = $this->dispatcher->getController();

            if ($area) {
                $dir = "@app/Areas/$area/Views/$controller";
            } else {
                $dir = "@views/$controller";
            }

            if (!isset($this->_dirs[$dir])) {
                $this->_dirs[$dir] = LocalFS::dirExists($dir);
            }

            if ($this->_dirs[$dir]) {
                $template = $dir . '/' . ucfirst($action);
            } elseif ($action === 'index') {
                $template = $dir;
            } else {
                $template = $dir . '/' . ucfirst($action);
            }
        }

        $this->fireEvent('view:rendering');

        $context->content = $this->_render($template, $context->vars, false);

        if ($context->layout !== false) {
            $layout = $this->_findLayout();
            $context->content = $this->_render($layout, $context->vars, false);
        }

        $this->fireEvent('view:rendered');

        if ($this->_autofix_url) {
            $this->fixUrl();
        }

        return $context->content;
    }

    /**
     * @return void
     */
    public function fixUrl()
    {
        $context = $this->_context;

        $context->content = preg_replace_callback('#\b(href|src|action|data-src)=(["\'`]{1,2})/(?!/)#', function ($match) {
            return "$match[1]=$match[2]{$this->_base_url}/";
        }, $context->content);
    }

    /**
     * @param string $template
     *
     * @return string|false
     */
    public function exists($template = null)
    {
        static $cached = [];

        if ($template === null) {
            $action = $this->dispatcher->getAction();
        } elseif (str_contains($template, '/')) {
            $action = null;
        } else {
            $action = $template;
            $template = null;
        }

        if ($template === null) {
            $area = $this->dispatcher->getArea();
            $controller = $this->dispatcher->getController();

            if ($area) {
                $dir = "@app/Areas/$area/Views/$controller";
            } else {
                $dir = "@views/$controller";
            }

            if (!isset($this->_dirs[$dir])) {
                $this->_dirs[$dir] = LocalFS::dirExists($dir);
            }

            if ($this->_dirs[$dir]) {
                $template = $dir . '/' . ucfirst($action);
            } elseif ($action === 'index') {
                $template = $dir;
            } else {
                return false;
            }
        }

        if (isset($cached[$template])) {
            return $cached[$template];
        } else {
            return $cached[$template] = $this->renderer->exists($template);
        }
    }

    /**
     * Renders a partial view
     *
     * @param string $path
     * @param array  $vars
     *
     * @return void
     */
    public function partial($path, $vars = [])
    {
        $this->_render($path, $vars, true);
    }

    /**
     * @param string $widget
     *
     * @return string|false
     */
    public function getWidgetClassName($widget)
    {
        if (str_contains($widget, '/')) {
            throw new MisuseException(['it is not allowed to access other area `:widget` widget', 'widget' => $widget]);
        }

        $area = $this->dispatcher->getArea();
        if ($area && class_exists($widgetClassName = "App\\Areas\\$area\\Widgets\\{$widget}Widget")) {
            return $widgetClassName;
        }

        return class_exists($widgetClassName = "App\\Widgets\\{$widget}Widget") ? $widgetClassName : false;
    }

    /**
     * @param string $widget
     * @param array  $options
     *
     * @return void
     */
    public function widget($widget, $options = [])
    {
        if ($options) {
            $this->setMaxAge(0);
        }

        if (!$widgetClassName = $this->getWidgetClassName($widget)) {
            throw new InvalidValueException(['`:widget` widget is invalid: `:class` class is not exists', 'widget' => $widget, 'class' => $widgetClassName]);
        }

        if (strpos($widgetClassName, '\\Areas\\')) {
            $view = "@app/Areas/{$this->dispatcher->getArea()}/Views/Widgets/$widget";
        } else {
            $view = "@views/Widgets/$widget";
        }

        /** @var \ManaPHP\View\WidgetInterface $widgetInstance */
        $widgetInstance = $this->_di->getShared($widgetClassName);
        $vars = $widgetInstance->run($options);

        if (is_string($vars)) {
            echo $vars;
        } else {
            $this->_render($view, $vars, true);
        }
    }

    /**
     * @param string $path
     * @param array  $vars
     *
     * @return void
     */
    public function block($path, $vars = [])
    {
        if ($path[0] !== '@' && !str_contains($path, '/')) {
            $path = "@views/Blocks/$path";
        }

        $this->_render($path, $vars, true);
    }

    /**
     * Externally sets the view content
     *
     * @param string $content
     *
     * @return static
     */
    public function setContent($content)
    {
        $context = $this->_context;

        $context->content = $content;

        return $this;
    }

    /**
     * Returns cached output from another view stage
     *
     * @return string
     */
    public function getContent()
    {
        return $this->_context->content;
    }

    public function dump()
    {
        $data = parent::dump();

        $data['_context']['content'] = '***';

        return $data;
    }
}