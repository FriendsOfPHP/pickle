<?php

namespace Pickle;

class PhpIni
{
    protected $raw;
    protected $path;
    protected $pickleHeader = ';Pickle installed extension, do not edit this line and below';
    protected $pickleFooter = ';Pickle installed extension, do not edit this line and above';

    protected $pickleHeaderStartPos = -1;
    protected $pickleHeaderEndPos = -1;
    protected $pickleFooterStartPos = -1;
    protected $pickleFooterEndPos = -1;

    public function __construct(PhpDetection $php)
    {
        $this->path = $php->getPhpIniDir();

        $this->raw = @file_get_contents($this->path);
        if (false === $this->raw) {
            throw new \Exception('Cannot read php.ini');
        }
        
        $this->setupPickleSectionPositions();
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
        foreach ($lines as $l) {
            $l = trim($l);
            if (0 !== strpos($l, "extension")) {
                continue;
            }
            list(, $dllname) = explode('=', $l);
            if (in_array(trim($dllname), $dlls)) {
                continue;
            }
            $new[] = $l;
        }

        return implode("\n", $new);
    }


    protected function setupPickleSectionPositions()
    {
        $posHeader = strpos($this->raw, $this->pickleHeader);
        if (false === $posHeader) {
            /* no pickle section here yet */
            return;
        }

        $this->pickleHeaderStartPos = $posHeader;
        $this->pickleHeaderEndPos = $this->pickleHeaderStartPos + strlen($this->pickleHeader);

        $posFooter = strpos($this->raw, $this->pickleFooter);
        if (false === $posFooter) {
            /* This is bad, no end of section marker, will have to lookup. The strategy is
                - look for the last extension directve after the header
                - extension directives are expected to come one after another one per line
                - comments are not expected inbetveen
                - mark the next pos after the last extension directive as the footer pos
            */
            $pos = $this->pickleHeaderEndPos;
            do {
                $pos = strpos($this->raw, "extension", $pos);
                if (false !== $pos) {
                    $this->pickleFooterStartPos = $pos;
                    $pos++;
                }
            } while (false !== $pos);


            $this->pickleFooterStartPos = strpos($this->raw, "\n", $this->pickleFooterStartPos);
        } else {
            $this->pickleFooterStartPos = $posFooter;
            $this->pickleFooterEndPos = $this->pickleFooterStartPos + strlen($this->pickleFooter);
        }
    }

    protected function getPickleSection()
    {
        return substr($this->raw, $this->pickleHeaderEndPos, $this->pickleFooterStartPos - $this->pickleHeaderEndPos);
    }

    public function updatePickleSection(array $dlls)
    {
        $before = "";
        $after = "";

        $pickleSection = '';
        foreach ($dlls as $dll) {
            $pickleSection .=  'extension=' . $dll . "\n";
        }

        if ($this->pickleHeaderStartPos > 0) {
            $pickleSection = $this->rebuildPickleParts($this->getPickleSection(), $dlls) . "\n" . $pickleSection;

            $before = substr($this->raw, 0, $this->pickleHeaderStartPos);
            
            /* If the footer end pos is < 0, there was no footer in php.ini. In this case the footer start pos
               means the end of the last extension directive after the header start, where the footer should  be */
            if ($this->pickleFooterEndPos > 0) {
                $after = substr($this->raw, $this->pickleFooterEndPos);
            } else {
                $after = substr($this->raw, $this->pickleFooterStartPos);
            }

            $before = rtrim($before);
            $after = ltrim($after);
        }

        $this->raw = $before . "\n\n" . $this->pickleHeader . "\n" . trim($pickleSection) . "\n" . $this->pickleFooter . "\n\n" . $after;
        if (!@file_put_contents($this->path, $this->raw)) {
            throw new \Exception('Cannot update php.ini');
        }

    }
}

