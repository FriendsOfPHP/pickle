<?php
namespace Pickle;

class Package
{
    private $path;
    private $archive_name;
    private $pkg;

    public function __construct($path)
    {
        $path = realpath($path);
        var_dump($path);
        $json_path = $path . '/pickle.json';
        var_dump($json_path);
        $this->path = $path;
        $this->pkg = json_decode(file_get_contents($json_path));
        if (!$this->pkg) {
            throw new \Exception('Cannot read or parse pickle.json');
        }
    }

    public function getName()
    {
        return $this->pkg->name;
    }

    public function getRootDir()
    {
        return $this->path;
    }

    public function getVersion()
    {
        if (!isset($this->pkg->version)) {
            $rel = glob($this->path . "/RELEASE-*");
            if (empty($rel)) {
                throw new \Exception('Cannot find any RELEASE file');
            }
            $sort_version = function ($a, $b) {
                $a_release = str_replace('RELEASE-', '', basename($a));
                $b_release = str_replace('RELEASE-', '', basename($b));

                return version_compare($a_release, $b_release);
            };

            usort($rel, $sort_version);
            $last_release = $rel[count($rel) - 1];
            $last_release = str_replace('RELEASE-', '', basename($last_release));
            $this->pkg->version = $last_release;
        }

        return $this->pkg->version;
    }

    public function getStatus()
    {
        if (!isset($this->pkg->status)) {
            $release_file = 'RELEASE-' . $this->getVersion();
            $release = file_get_contents($this->path . '/' . $release_file);
            if (preg_match("/Package state:\s+(.*)/", $release, $match)) {
                $release_state = $match[1];
            } else {
                throw new \Exception('RELEASE file has no or invalid state');
            }
            $this->pkg->state = $release_state;
        }

        return $this->pkg->state;
    }

    public function getAuthors()
    {
        if (!isset($this->pkg->authors)) {
            $credits = file($this->path . '/CREDITS');
            $authors = [];
            foreach ($credits as $l) {
                $line = explode(' ', $l);
                array_walk($line,
                    function (&$value, $key) {
                        $value = str_replace(['(',')'], ['',''], trim($value));
                    });
                    if (empty($line[0]) || empty($line[1]) || empty($line[2])) {
                        throw new \Exception('CREDITS file invalid or imcomplete');
                    }
                    $author['name']     = $line[0];
                    $author['handle']   = $line[1];
                    $author['email']    = $line[2];
                    $author['homepage']  = $line[3];
                    $authors[] = $author;
            }
            if (count($authors) < 1) {
                throw new \Exception('CREDITS file invalid or imcomplete');
            }
            $this->pkg->authors = $authors;
        }

        return $this->pkg->authors;
    }

    public function getGitIgnoreFiles()
    {
        $file = $this->path . "/.gitignore";
        $dir = $this->path;
        $matches = array();
        $lines = file($file);

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
        $all = array();
        $dir = $this->path;
        while ($dirs = glob($dir . '*')) {
            $dir .= '/*';
            $files = array_diff($all, $ignorefiles);
            if (!$all) {
                $all = $dirs;
            } else {
                $all = array_merge($all, $dirs);
            }
        }

        return $files;
    }
    public function getConfigureOptions()
    {
        return $this->pkg->extra->{'configure-options'};
    }

    public function getReleaseJson()
    {
        $json = json_encode($this->pkg, JSON_PRETTY_PRINT);
        if (!$json) {
            throw new \Exception('Fail to encode pickle.json');
        }

        return $json;
    }
}
