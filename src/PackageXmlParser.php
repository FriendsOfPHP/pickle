<?php
namespace Pickle;

class PackageXmlParser
{
    /**
     * @var string
     */
    public $path;

    /**
     * Constructor
     *
     * @param string $path
     */
    public function __construct($path = '')
    {
        $this->path = $path;
        $this->pkg = rtrim($path, '/') . '/package.xml';
    }

    /**
     * Parse xml
     *
     * @return \SimpleXMLElement
     */
    public function parse()
    {
        $sx = simplexml_load_file($this->pkg);

        if (!($sx['version'] == "2.0" || $sx['version'] == "2.1")) {
            throw new \Exception('Unsupported package.xml version, 2.0 or later only is supported');
        }

        if (!isset($sx->providesextension)) {
            throw new \Exception('Only extension packages are supported');
        }

        return $sx;
    }
}
