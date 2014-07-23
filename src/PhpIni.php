<?php

namespace Pickle;

class PhpIni
{
    protected $raw;
    protected $path;
    protected $pickleHeader = ';Pickle installed extension, do not edit this line and below';

    public function __construct(PhpDetection $php)
    {
        $this->path = $php->getPhpIniDir();

        $this->raw = @file_get_contents($this->path);
        if (false === $this->raw) {
            throw new \Exception('Cannot read php.ini');
        }
        
    }

    /**
     * @param string $pickleSection
     * @param array  $dlls
     *
     * @return string
     */
    protected function rebuildPickleParts($pickleSection, array $dlls)
    {
        $lines = explode("\n", $pickleSection);
        $new = [];
        array_shift($lines);
        foreach ($lines as $l) {
            $l = trim($l);
            if ($l == '') {
                continue;
            }
            list(, $dllname) = explode('=', $l);
            if (in_array(trim($dllname), $dlls)) {
                continue;
            }
            $new[] = $l;
        }

        return implode($new, "\n");
    }

    public function updatePickleSection(array $dlls)
    {
        $posHeader = strpos($this->raw, $this->pickleHeader);

        $new = '';
        foreach ($dlls as $dll) {
            $new .=  "\n" . 'extension=' . $dll . "\n";
        }

        $pickleSection = '';
        if ($posHeader !== false) {
            $pickleSection = substr($this->raw, $posHeader);
            $pickleSection = $this->rebuildPickleParts($pickleSection, $dlls);
        }

        $this->raw = substr($this->raw, 0, $posHeader - 1) . "\n" . $this->pickleHeader . "\n" . $pickleSection . $new;
        if (!@file_put_contents($this->path, $this->raw)) {
            throw new \Exception('Cannot update php.ini');
        }

    }
}

