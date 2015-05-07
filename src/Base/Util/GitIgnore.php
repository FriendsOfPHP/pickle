<?php

namespace Pickle\Base\Util;

use Pickle\Base\Interfaces;

class GitIgnore
{
    protected $excluded = [];

    public function __construct(Interfaces\Package $package)
    {
        $dir = $package->getSourceDir();
        $path = $package->getSourceDir().'/.gitignore';
        $this->excluded = glob("$dir/.git/*");

        $this->excluded = [
            "$dir/.git/", "$dir/.gitignore", "$dir/.gitmodules",
        ];
        if (is_file($path) === false) {
            throw new \InvalidArgumentException('File not found: '.$path);
        }

        foreach (file($path) as $line) {
            $line = trim($line);

            // empty line or comment
            if ('' === $line || '#' === $line[0]) {
                continue;
            }

            // negated glob
            if ('!' === $line[0]) {
                $line = substr($line, 1);
                $files = array_diff(glob("$dir/*"), glob("$dir/$line"));
            // normal glob
            } else {
                $files = [];

                if (substr($line, -1) !== '/') {
                    $files = glob("$dir/$line");

                    $line .= '/';
                }

                $files = array_merge(glob("$dir/$line*"), $files);
            }

            $this->excluded = array_merge($this->excluded, $files);
        }
    }

    public function __invoke(\SplFileInfo $file)
    {
        return $this->isExcluded($file) === false;
    }

    public function isExcluded(\SplFileInfo $file)
    {
        foreach ($this->excluded as $path) {
            if (!strncmp($file,  $path, strlen($path))) {
                return true;
            }
        }

        return false;
    }
}

/* vim: set tabstop=4 shiftwidth=4 expandtab: fdm=marker */
