<?php
namespace Pickle\Package\XML;

use Composer\Package\Loader\LoaderInterface;
use Pickle\Package;

class Loader
{
    protected $loader;

    public function __construct(LoaderInterface $loader)
    {
        $this->loader = $loader;
    }

    /**
     * @param string $path
     */
    public function load($path)
    {
        if (is_file($path) === false) {
            throw new \InvalidArgumentException('File not found: ' . $path);
        }

        $xml = @simplexml_load_file($path);

        if ($xml === false) {
            $error = error_get_last();
            $exception = null;

            if (null !== $error) {
                $exception = new \Exception($error['message'], $error['type']);
            }

            throw new \RuntimeException('Failed to read ' . $path, 0, $exception);
        }

        $this->validate($xml);

        $package = [
            'name' => (string) $xml->name,
            'version' => (string) $xml->version->release,
            'stability' => (string) $xml->stability->release,
            'description' => (string) $xml->summary,
        ];

        $authors = array_merge(
            iterator_to_array($xml->lead),
            iterator_to_array($xml->developer),
            iterator_to_array($xml->contributor),
            iterator_to_array($xml->helper)
        );

        if (empty($authors) === false) {
            $package['authors'] = [];

            foreach ($authors as $author) {
                $package['authors'][] = [
                    'name' => (string) $author->name,
                    'email' => (string) $author->email,
                ];
            }
        }

        $opts = $configureOptions = [];

        if (isset($xml->extsrcrelease->configureoption)) {
            $opts = $xml->extsrcrelease->configureoption;
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

        if (empty($configureOptions) === false) {
            $package['extra'] = ['configure-options' => $configureOptions];
        }

        if (isset($xml->license)) {
            $package['license'] = (string) $xml->license;
        }

        return $this->loader->load($package);
    }

    protected function validate(\SimpleXMLElement $xml)
    {
        if (version_compare($xml['version'], '2.0') === -1) {
            throw new \RuntimeException('Unsupported package.xml version, 2.0 or later only is supported');
        }

        if (!isset($xml->providesextension)) {
            throw new \RuntimeException('Only extension packages are supported');
        }
    }
}
