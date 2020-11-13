<?php

use ManaPHP\Di;

if (!function_exists('attr_nv')) {
    /**
     * @param string $name
     * @param string $default
     *
     * @return string
     */
    function attr_nv($name, $default = '')
    {
        return sprintf('name="%s" value="%s"', $name, e(input($name, $default)));
    }
}

if (!function_exists('attr_inv')) {
    /**
     * @param string $name
     * @param string $default
     *
     * @return string
     */
    function attr_inv($name, $default = '')
    {
        if ($pos = strpos($name, '[')) {
            $id = substr($name, $pos + 1, -1);
        } else {
            $id = $name;
        }

        return sprintf('id="%s" name="%s" value="%s"', $id, $name, e(input($name, $default)));
    }
}

if (!function_exists('widget')) {
    /**
     * @param string $name
     * @param array  $vars
     *
     * @return string|array
     */
    function widget($name, $vars = [])
    {
        static $view;
        if (!$view) {
            $view = Di::getDefault()->getShared('view');
        }

        return $view->widget($name, $vars);
    }
}

if (!function_exists('partial')) {
    /**
     * @param string $path
     * @param array  $vars
     *
     * @return void
     */
    function partial($path, $vars = [])
    {
        static $view;
        if (!$view) {
            $view = Di::getDefault()->getShared('view');
        }

        $view->partial($path, $vars);
    }
}

if (!function_exists('block')) {
    /**
     * @param string $path
     * @param array  $vars
     *
     * @return void
     */
    function block($path, $vars = [])
    {
        static $view;
        if (!$view) {
            $view = Di::getDefault()->getShared('view');
        }

        $view->block($path, $vars);
    }
}

if (!function_exists('layout')) {

    /**
     * @param string|false $template
     */
    function layout($template = 'Default')
    {
        static $view;
        if (!$view) {
            $view = Di::getDefault()->getShared('view');
        }

        $view->setLayout($template);
    }
}

if (!function_exists('csrf_token')) {
    /**
     * @return string
     */
    function csrf_token()
    {
        return di('csrfPlugin')->get();
    }
}

if (!function_exists('csrf_field')) {
    /**
     * @return string
     */
    function csrf_field()
    {
        $csrfToken = di('csrfPlugin');
        return sprintf('<input type="hidden" name="%s" value="%s" />', $csrfToken->getName(), $csrfToken->get());
    }
}

if (!function_exists('bundle')) {
    /**
     * @param array  $files
     * @param string $name
     *
     * @return string
     */
    function bundle($files, $name = 'app')
    {
        return di('assetBundle')->bundle($files, $name);
    }
}

if (!function_exists('action')) {

    /**
     * @param array|string $args
     * @param bool|string  $scheme
     *
     * @return string
     */
    function action($args = [], $scheme = false)
    {
        static $router;
        if (!$router) {
            $router = Di::getDefault()->getShared('router');
        }

        return $router->createUrl($args, $scheme);
    }
}

if (!function_exists('url')) {
    /**
     * @param string|array $args
     * @param bool|string  $scheme
     *
     * @return string
     */
    function url($args, $scheme = false)
    {
        static $url;
        if (!$url) {
            $url = Di::getDefault()->getShared('url');
        }

        return $url->get($args, $scheme);
    }
}

if (!function_exists('asset')) {
    /**
     * @param string $path
     *
     * @return string
     */
    function asset($path)
    {
        static $alias;
        if (!$alias) {
            $alias = di('alias');
        }

        static $paths = [];

        if (isset($paths[$path])) {
            return $paths[$path];
        }

        if (!str_contains($path, '?') && is_file($file = $alias->get('@public') . $path)) {
            return $paths[$path] = $alias->get('@asset') . $path . '?' . substr(md5_file($file), 0, 12);
        } else {
            return $paths[$path] = $alias->get('@asset') . $path;
        }
    }
}