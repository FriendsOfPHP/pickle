<?php
namespace Pickle;

class ConvertXml
{
    private $pkg;
    private $path;
    private $configure_options = [];

    /**
     *
     * Constructor
     *
     * @param \SimpleXmlElement $package
     *
     * @param string $path
     *
     */
    public function __construct(\SimpleXmlElement $package, $path)
    {
        $this->pkg = $package;
        $this->path = $path;
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
        $lead = $this->pkg->lead;
        $developer = $this->pkg->developer;
        $contributor = $this->pkg->contributor;
        $helper = $this->pkg->helper;

        $out = "";

        foreach ($lead as $l) {
            $out .= sprintf("%s (%s) (%s) (%s)\n", $l->name, $l->user, $l->email, $l->active);
        }
        foreach ($developer as $l) {
            $out .= sprintf("%s (%s) (%s) (%s)\n", $l->name, $l->user, $l->email, $l->active);
        }
        foreach ($contributor as $l) {
            $out .= sprintf("%s (%s) (%s) (%s)\n", $l->name, $l->user, $l->email, $l->active);
        }
        foreach ($helper as $l) {
            $out .= sprintf("%s (%s) (%s) (%s)\n", $l->name, $l->user, $l->email, $l->active);
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
        $summary = $this->pkg->summary;
        $description = $this->pkg->description;
        file_put_contents($this->path . '/README', $summary . "\n\n" . $description);
    }

    /**
     *
     * Release the package
     *
     * @param \Simplexmlelement $release Default null
     *
     */
    public function release(\SimpleXmlElement $release = null)
    {
        if ($release == null) {
            $package_version  = $this->pkg->version->release;
            $package_state    = $this->pkg->stability->release;
            $api_version      = $this->pkg->version->api;
            $api_state        = $this->pkg->stability->api;
            $release_notes    = $this->pkg->notes;
            $release_date     = $this->pkg->date;
        } else {
            $package_version  = $release->version->release;
            $package_state    = $release->stability->release;
            $api_version      = $release->version->api;
            $api_state        = $release->stability->api;
            $release_notes    = $release->notes;
            $release_date     = $release->date;
        }

        $out  = "Date:             $release_date\n";
        $out .= "Package version:  $package_version\n";
        $out .= "Package state:    $package_state\n";
        $out .= "API Version:      $api_version\n";
        $out .= "API state:        $api_state\n\n";
        $out .= "Changelog:\n";
        $out .= $release_notes;
        file_put_contents($this->path . '/RELEASE-' . $package_version, $out);
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
        $changelog = $this->pkg->changelog->release;
        foreach ($changelog as $release) {
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
        $out .= $this->pkg->license;
        file_put_contents($this->path . '/LICENSE', $out);
    }

    /**
     *
     *
     */
    public function extsrcrelease()
    {
        if (isset($this->pkg->extsrcrelease->configureoption)) {
            $configureoption = $this->pkg->extsrcrelease->configureoption;
        } else {
            return;
        }

        foreach ($configureoption as $opt) {
            $name     = trim($opt['name']);
            $default  = trim($opt['default']);
            $prompt   = trim($opt['prompt']);

            $this->configure_options[$name] = [
                'default'  => $default,
                'prompt'   => $prompt
            ];
        }
    }

    /**
     *
     * Generate the pickle.json
     *
     */
    public function generateJson()
    {
        $out = [
                'name' => strtolower($this->pkg->name),
                'type' => 'extension',
                'extra' => []
            ];
        $out['extra']['configure-options'] = $this->configure_options;
        $json =  json_encode($out, JSON_PRETTY_PRINT);

        file_put_contents($this->path . '/pickle.json', $json);
    }
}
