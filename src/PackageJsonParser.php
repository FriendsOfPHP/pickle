<?php
namespace Pickle;

class PackageJsonParser
{
    private $path;
    private $archive_name;
    private $pkg;

    public function __construct($path)
    {
        $this->path = $path;
        $this->pkg = json_decode(file_get_contents($path));
    }

    public function getGitIgnoreFiles()
    {
        $file = $this->path . "/.gitignore";
        $dir = $this->path;
        $matches = array();
        $lines = file($file);

        var_dump($dir);

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') continue;                 # empty line
            if (substr($line, 0, 1) == '#') continue;   # a comment
            if (substr($line, 0, 1)== '!') {           # negated glob
                $line = substr($line, 1);
                $files = array_diff(glob("$dir/*"), glob("$dir/$line"));
            } else {                                    # normal glob
                $files = glob("$dir/$line");
            }
            $matches = array_merge($matches, $files);
        }

        return $matches;
    }

    public function getFiles()
    {
        $ignorefiles = $this->getGitIgnoreFiles();
        $all = glob($this->path . '/*');
        $files = array_diff($all, $ignorefiles);

        return $files;
    }
}
