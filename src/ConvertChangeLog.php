<?php
namespace Pickle;

class ConvertChangeLog
{
    private $path;
    private $changelog;

    public function __construct($path)
    {
        if (false === is_file($path)) {
            throw new \InvalidArgumentException('File not found: ' . $path);
        }

        $this->path = $path;
    }

    public function parse()
    {
        $xml = @simplexml_load_file($this->path);

        $changelog = [];
        $current = new \StdClass;
        $current->date = $xml->date;
        $current->time = $xml->time;
        $current->version = new \StdClass;
        $current->version->release = $xml->version->release;
        $current->stability = new \StdClass;
        $current->stability->release = $xml->stability->release;
        $current->notes = $xml->notes;

        $changelog[] = $current;
        if (isset($xml->changelog->release)) {
            foreach ($xml->changelog->release as $release) {
                $changelog[] = $release;
            }
        }
        $this->changelog = $changelog;
    }

    public function generateReleaseFile()
    {
        if (empty($this->changelog)) {
            return;
        }

        $contents = '';
        foreach ($this->changelog as $cl) {
            $contents .= 'Version: ' . $cl->version->release . "\n" .
                     'Date: ' . $cl->date . ' '. $cl->time . "\n" .
                     'Stability: ' . $cl->stability->release . "\n" .
                     "\n" .
                     'notes: ' . $cl->notes . "\n" .
                     "\n" .
                     "\n" .
                     "\n";
        }

        if (file_put_contents(dirname($this->path) . DIRECTORY_SEPARATOR . 'RELEASES', $contents) === false) {
            throw new \RuntimeException('cannot save RELEASE file in <' . dirname($this->path) . DIRECTORY_SEPARATOR . 'RELEASES>');
        }
    }
}
