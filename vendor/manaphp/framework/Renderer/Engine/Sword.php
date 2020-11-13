<?php

namespace ManaPHP\Renderer\Engine;

use ManaPHP\Component;
use ManaPHP\Renderer\EngineInterface;

/**
 * Class ManaPHP\Renderer\Engine\Sword
 *
 * @package renderer\engine
 *
 * @property-read \ManaPHP\Renderer\Engine\Sword\Compiler $swordCompiler
 */
class Sword extends Component implements EngineInterface
{
    /**
     * @var string
     */
    protected $_doc_root;

    /**
     * @var array
     */
    protected $_compiled = [];

    /**
     * Sword constructor.
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        $this->_doc_root = $options['doc_root'] ?? $_SERVER['DOCUMENT_ROOT'];
    }

    /**
     * @param string $source
     *
     * @return string
     */
    public function getCompiledFile($source)
    {
        if (str_starts_with($source, $root = $this->alias->get('@root'))) {
            $compiled = '@data/sword' . substr($source, strlen($root));
        } elseif ($this->_doc_root !== '' && str_starts_with($source, $this->_doc_root)) {
            $compiled = '@data/sword/' . substr($source, strlen($this->_doc_root));
        } else {
            $compiled = "@data/sword/$source";
            if (DIRECTORY_SEPARATOR === '\\') {
                $compiled = str_replace(':', '_', $compiled);
            }
        }

        $compiled = $this->alias->resolve($compiled);

        if ($this->configure->debug || !file_exists($compiled) || filemtime($source) > filemtime($compiled)) {
            $this->swordCompiler->compileFile($source, $compiled);
        }

        return $compiled;
    }

    /**
     * @param string $file
     * @param array  $vars
     *
     * @return void
     */
    public function render($file, $vars = [])
    {
        extract($vars, EXTR_SKIP);

        if (!isset($this->_compiled[$file])) {
            $this->_compiled[$file] = $this->getCompiledFile($file);
        }

        /** @noinspection PhpIncludeInspection */
        require $this->_compiled[$file];
    }
}