<?php
namespace Pickle;

class Package
{
    use GitIgnore;

    /**
     * @var string
     */
    private $path;

    /**
     * @var string
     */
    private $archive_name;

    /**
     * @var string
     */
    private $pkg;

    /**
     *
     * @pram string $path Path to pickle.json
     *
     */
    public function __construct($path)
    {
        $path = realpath($path);
        if (!$path) {
            throw new \Exception('Cannot read/access ' . $path);
        }
        $json_path = $path . '/pickle.json';
        $this->path = $path;
        $this->pkg = json_decode(file_get_contents($json_path));
        if (!$this->pkg) {
            throw new \Exception('Cannot read or parse pickle.json');
        }
    }

    /**
     *
     * Get the package name
     *
     * @return string
     *
     */
    public function getName()
    {
        return $this->pkg->name;
    }

    /**
     *
     * Get the root directory path
     *
     * @return string
     *
     */
    public function getRootDir()
    {
        return $this->path;
    }

    /**
     *
     * Get the latest package version
     *
     * @return string
     *
     */
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

    /**
     *
     * Get the package status
     *
     * @return string
     *
     */
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

    /**
     *
     * Get the authors
     *
     * @return array
     *
     */
    public function getAuthors()
    {
        if (!isset($this->pkg->authors)) {
            $credits = file($this->path . '/CREDITS');
            $authors = [];
            foreach ($credits as $l) {
                $line = explode(' ', $l);
                array_walk(
                    $line,
                    function (&$value, $key) {
                        $value = str_replace(['(', ')'], ['', ''], trim($value));
                    }
                );
                if (empty($line[0]) || empty($line[1]) || empty($line[2])) {
                    throw new \Exception('CREDITS file invalid or imcomplete');
                }
                $author['name'] = $line[0];
                $author['handle'] = $line[1];
                $author['email'] = $line[2];
                $author['homepage'] = $line[3];
                $authors[] = $author;
            }
            if (count($authors) < 1) {
                throw new \Exception('CREDITS file invalid or imcomplete');
            }
            $this->pkg->authors = $authors;
        }

        return $this->pkg->authors;
    }

    /**
     *
     * Get files, will not return gitignore files
     *
     * @return array
     *
     */
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

    /* If someone prefers a nice regex for both AC_ and PHP_... :) */
    protected function fetchArgAc($which, $config)
    {
        $next = 0;
        $options = [];
        $type = strpos($which, 'ENABLE') !== FALSE ? 'enable' : 'with';
        $default = true;
        while (($s = strpos($config, $which, $next)) !== FALSE) {
            $s = strpos($config, '(', $s);
            $e = strpos($config, ')', $s + 1);
            $option = substr($config, $s + 1, $e - $s);

            if ($type == 'enable') {
                $default = (strpos($option, '-disable-') !== false) ? true : false;
            } elseif ($type == 'with') {
                $default = (strpos($option, '-without-') !== false) ? true : false;
            }

            list($name, $desc) = explode(',', $option);

            $desc = preg_replace('![\s]+!', ' ', trim($desc));
            $desc = trim(substr($desc, 1, strlen($desc) - 2));

            $s_a = strpos($desc, ' ');
            $desc = trim(substr($desc, $s_a));

            $options[$name] = (object) [
                'prompt'  => $desc,
                'type'    => $type,
                'default' => $default
            ];
            $next = $e + 1;
        }

        return $options;
    }

    protected function fetchArg($which, $config)
    {
        $next = 0;
        $options = [];

        $type = strpos($which, 'ENABLE') !== FALSE ? 'enable' : 'with';
        $default = 'y';
        while (($s = strpos($config, $which, $next)) !== FALSE) {
            $s = strpos($config, '(', $s);
            $e = strpos($config, ')', $s + 1);
            $option = substr($config, $s + 1, $e - $s);
            list($name, $desc) = explode(',', $option);

            if ($type == 'enable') {
                $default = (strpos($option, '-disable-') !== false) ? true : false;
            } elseif ($type == 'with') {
                $default = (strpos($option, '-without-') !== false) ? true : false;
            }

            $options[$name] = (object) [
                'prompt'  => $desc,
                'type'    => $type,
                'default' => $default
            ];
            $next = $e + 1;
        }

        return $options;
    }

    /**
     * get configurable options
     *
     * @return array
     *
     */
    public function getConfigureOptions()
    {
        if (!isset($this->pkg->configure_options)) {
            $config = file_get_contents($this->path . '/config.m4');
            $options['with'] = $this->fetchArg('PHP_ARG_WITH', $config);
            $t = $this->fetchArgAc('AC_ARG_WITH', $config);
            $options['with'] = array_merge($options['with'], $t);

            $options['enable'] = $this->fetchArg('PHP_ARG_ENABLE', $config);
            $t = $this->fetchArgAc('AC_ARG_ENABLE', $config);
            $options['enable'] = array_merge($options['enable'], $t);
            $this->pkg->extra->configure_options = $options;
        }
        print_r($this->pkg->extra->configure_options);

        return $this->pkg->extra->configure_options;
    }

    /**
     *
     * return json string if the package information can be json_encoded
     *
     * @return string json_encoded string
     *
     */
    public function getReleaseJson()
    {
        $json = json_encode($this->pkg, JSON_PRETTY_PRINT);
        if (!$json) {
            throw new \Exception('Fail to encode pickle.json');
        }

        return $json;
    }
}
