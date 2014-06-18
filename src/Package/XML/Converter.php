<?php
namespace Pickle\Package\XML;

use Pickle\Package;

class Converter
{
    /**
     * @var string Package's root directory
     */
    private $path;

    /**
     * @var Parser Package definition parser
     */
    private $parser;

    /**
     * @param string             $path   Package's root directory
     * @param Package\XML\Parser $parser Package definition parser
     */
    public function __construct($path, Package\XML\Parser $parser)
    {
        $this->path = $path;
        $this->parser = $parser;
    }

    /**
     * Convert package.xml to pickle format
     *
     * @return Package
     */
    public function convert()
    {
        $this->maintainers();
        $this->summary();
        $this->release();
        $this->changelog();

        if (file_exists($this->path . "/LICENSE") === false) {
            $this->license();
        }

        $this->generateJson();

        return new Package($this->path);
    }

    /**
     *
     * Create credits
     *
     * @return void
     *
     */
    public function maintainers()
    {
        $out = '';

        foreach ($this->parser->getAuthors() as $developer) {
            $out .= sprintf("%s (%s) (%s) (%s)\n", $developer->name, $developer->user, $developer->email, $developer->active);
        }

        file_put_contents($this->path . '/CREDITS', $out);
    }

    /**
     *
     * Create Summary
     *
     * @return void
     *
     */
    public function summary()
    {
        $summary = $this->parser->getSummary();
        $description = $this->parser->getDescription();

        file_put_contents($this->path . '/README', $summary . "\n\n" . $description);
    }

    /**
     *
     * Release the package
     *
     * @param array $release Release to write (defaults to current release)
     *
     * @return void
     */
    public function release(array $release = null)
    {
        if (null === $release) {
            $release = $this->parser->getCurrentRelease();
        }

        $out  = 'Date:             ' . $release['date'] . PHP_EOL;
        $out .= 'Package version:  ' . $release['version'] . PHP_EOL;
        $out .= 'Package state:    ' . $release['status'] . PHP_EOL;
        $out .= 'API Version:      ' . $release['api']['version'] . PHP_EOL;
        $out .= 'API state:        ' . $release['api']['status'] . PHP_EOL . PHP_EOL;
        $out .= 'Changelog:' . PHP_EOL;
        $out .= $release['notes'];

        file_put_contents($this->path . '/RELEASE-' . $release['version'], $out);
    }

    /**
     *
     * Build the release information from the changelog release
     *
     * @see release
     *
     * @return void
     *
     */
    public function changelog()
    {
        foreach ($this->parser->getPastReleases() as $release) {
            $this->release($release);
        }
    }

    /**
     *
     * Put the license
     *
     * @return void
     *
     */
    public function license()
    {
        $out  = "This package is under the following license(s):\n";
        $out .= $this->parser->getCurrentRelease()['license'];

        file_put_contents($this->path . '/LICENSE', $out);
    }

    /**
     *
     * Generate the pickle.json
     *
     */
    public function generateJson()
    {
        $out = [
            'name' => strtolower($this->parser->getName()),
            'type' => 'extension',
            'extra' => [
                'configure-options' => $this->parser->getExtraOptions()
            ]
        ];

        file_put_contents($this->path . '/pickle.json', json_encode($out, JSON_PRETTY_PRINT));
    }
}
