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
        if (empty($path)) {
            $this->path = getcwd() . '/package.xml';
        } else {
            $this->path = rtrim($path, '/') . '/package.xml';
        }
    }

    /**
     * Parse xml
     *
     * @return \SimpleXMLElement
     */
    public function parse()
    {
        if (file_exists($this->path) === false) {
            throw new \Exception('File not found: ' . $this->path);
        }

        $sx = @simplexml_load_file($this->path);

        if ($sx === false) {
            $error = error_get_last();
            $exception = null;

            if (null !== $error) {
                $exception = new \Exception($error['message'], $error['type']);
            }

            throw new \Exception('Failed to read ' . $this->path, 0, $exception);
        }

        if (!($sx['version'] == "2.0" || $sx['version'] == "2.1")) {
            throw new \Exception('Unsupported package.xml version, 2.0 or later only is supported');
        }

        if (!isset($sx->providesextension)) {
            throw new \Exception('Only extension packages are supported');
        }

        echo "Packager Version: " . $sx['packagerversion'] . "\n";
        echo "XML Version: " . $sx['version'] . "\n";
        echo "Extension pkg: " . $sx->providesextension . "\n";
        echo "Package name: " . $sx->name . "\n";
        echo "Package version: " . $sx->version->release . "\n";

        return $sx;
    }
}
