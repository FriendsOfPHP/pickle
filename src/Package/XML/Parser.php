<?php
namespace Pickle\Package\XML;

use Pickle\Package;

class Parser extends Package\Parser
{
    private $path;

    /**
     * @var \SimpleXmlElement
     */
    private $xml;

    /**
     * Constructor
     *
     * @param string $path Path to the package root directory
     *
     * @throws \InvalidArgumentException If the directory does not exist
     */
    public function __construct($path)
    {
        parent::__construct($path);

        $this->path = realpath($this->root . '/package.xml');

        if (false === $this->path) {
            throw new \InvalidArgumentException('File not found: ' . $this->root . '/package.xml');
        }
    }

    /**
     * @throws \RuntimeException If package.xml could not be read
     * @throws \RuntimeException If package.xml version is not supported
     */
    public function parse()
    {
        $this->xml = @simplexml_load_file($this->path);

        if ($this->xml === false) {
            $error = error_get_last();
            $exception = null;

            if (null !== $error) {
                $exception = new \Exception($error['message'], $error['type']);
            }

            throw new \RuntimeException('Failed to read ' . $this->path, 0, $exception);
        }

        if (version_compare($this->xml['version'], '2.0') === -1) {
            throw new \RuntimeException('Unsupported package.xml version, 2.0 or later only is supported');
        }

        if (!isset($this->xml->providesextension)) {
            throw new \RuntimeException('Only extension packages are supported');
        }
    }

    public function getName()
    {
        return $this->xml->name;
    }

    public function getVersion()
    {
        return $this->xml->version->release;
    }

    public function getStatus()
    {
        return $this->xml->stability->release;
    }

    public function getAuthors()
    {
        return array_merge(
            iterator_to_array($this->xml->lead),
            iterator_to_array($this->xml->developer),
            iterator_to_array($this->xml->contributor),
            iterator_to_array($this->xml->helper)
        );
    }

    public function getPackagerVersion()
    {
        return $this->xml['packagerversion'];
    }

    public function getXMLVersion()
    {
        return $this->xml['version'];
    }

    public function getProvidedExtension()
    {
        return $this->xml->providesextension;
    }

    public function getSummary()
    {
        return $this->xml->summary;
    }

    public function getDescription()
    {
        return $this->xml->description;
    }

    public function getCurrentRelease()
    {
        return $this->formatRelease($this->xml);
    }

    public function getPastReleases()
    {
        $releases = array();

        if (isset($this->xml->changelog->release)) {
            foreach ($this->xml->changelog->release as $release) {
                if(empty($release)) {
                    continue;
                }

                $releases[] = $this->formatRelease($release);
            }
        }

        return $releases;
    }

    protected function formatRelease(\SimpleXMLElement $release)
    {
        return [
            'version' => $release->version->release,
            'status' => $release->stability->release,
            'date' => $release->date,
            'license' => $release->license,
            'notes' => $release->notes,
            'api' => [
                'version' => $release->version->api,
                'status' => $release->stability->api,
            ]
        ];
    }

    public function getExtraOptions()
    {
        $opts = $configureOptions = [];

        if (isset($this->xml->extsrcrelease->configureoption)) {
            $opts = $this->xml->extsrcrelease->configureoption;
        }

        foreach ($opts as $opt) {
            $name = trim($opt['name']);
            $default = trim($opt['default']);
            $prompt = trim($opt['prompt']);

            $configureOptions[$name] = [
                'default' => $default,
                'prompt' => $prompt
            ];
        }

        return $configureOptions;
    }
}
