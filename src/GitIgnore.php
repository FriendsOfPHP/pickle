<?php
namespace Pickle;

class GitIgnore
{
    protected $excluded = [];

    public function __construct(Package $package)
    {
        $dir = $package->getSourceDir();
        $path = $package->getSourceDir() . '/.gitignore';

        if (is_file($path) === false) {
            throw new \InvalidArgumentException('File not found: ' . $path);
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
                $files = glob("$dir/$line");
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
        return in_array($file->getRealPath(), $this->excluded);
    }
}
